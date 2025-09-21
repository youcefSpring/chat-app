<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\UploadFileRequest;
use App\Models\Attachment;
use App\Services\FileUploadService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private AuditService $auditService
    ) {}

    public function store(UploadFileRequest $request): JsonResponse
    {
        try {
            $fileData = $this->fileUploadService->uploadFile(
                $request->file('file'),
                $request->user()
            );

            $attachment = Attachment::create([
                'uploader_id' => $request->user()->id,
                'original_name' => $fileData['original_name'],
                'file_path' => $fileData['file_path'],
                'thumbnail_path' => $fileData['thumbnail_path'] ?? null,
                'mime_type' => $fileData['mime_type'],
                'size' => $fileData['size'],
                'metadata' => $fileData['metadata'],
            ]);

            $this->auditService->logFileUploaded($request->user(), $attachment);

            return response()->json($attachment, 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'UPLOAD_FAILED'
            ], 422);
        }
    }

    public function show(Request $request, Attachment $attachment)
    {
        if (!$this->userCanAccessAttachment($attachment, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            $url = $this->fileUploadService->getPresignedUrl($attachment->file_path, 60);

            return redirect($url);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'File not found',
                'code' => 'FILE_NOT_FOUND'
            ], 404);
        }
    }

    public function download(Request $request, Attachment $attachment)
    {
        if (!$this->userCanAccessAttachment($attachment, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            if (Storage::disk('s3')->exists($attachment->file_path)) {
                $url = $this->fileUploadService->getPresignedUrl($attachment->file_path, 60);

                return response()->json([
                    'download_url' => $url,
                    'filename' => $attachment->original_name,
                    'size' => $attachment->size,
                    'mime_type' => $attachment->mime_type
                ]);
            }

            return response()->json([
                'message' => 'File not found',
                'code' => 'FILE_NOT_FOUND'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Download failed',
                'code' => 'DOWNLOAD_FAILED'
            ], 500);
        }
    }

    public function thumbnail(Request $request, Attachment $attachment)
    {
        if (!$this->userCanAccessAttachment($attachment, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        if (!$attachment->thumbnail_path) {
            return response()->json([
                'message' => 'Thumbnail not available',
                'code' => 'THUMBNAIL_NOT_AVAILABLE'
            ], 404);
        }

        try {
            $url = $this->fileUploadService->getPresignedUrl($attachment->thumbnail_path, 60);

            return redirect($url);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Thumbnail not found',
                'code' => 'THUMBNAIL_NOT_FOUND'
            ], 404);
        }
    }

    public function destroy(Request $request, Attachment $attachment): JsonResponse
    {
        if (!$this->userCanDeleteAttachment($attachment, $request->user())) {
            return response()->json([
                'message' => 'Access denied',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        try {
            // Delete from storage
            $this->fileUploadService->deleteFile($attachment->file_path);

            if ($attachment->thumbnail_path) {
                $this->fileUploadService->deleteFile($attachment->thumbnail_path);
            }

            $this->auditService->logFileDeleted($request->user(), $attachment);

            // Delete from database
            $attachment->delete();

            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete file',
                'code' => 'DELETE_FAILED'
            ], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 50);
        $channelId = $request->get('channel_id');
        $fileType = $request->get('file_type'); // image, document, video, audio

        $query = Attachment::query()
            ->with(['message.channel:id,name', 'uploader:id,name'])
            ->whereHas('message.channel', function ($channelQuery) use ($request) {
                $channelQuery->where('organization_id', $request->user()->organization_id);

                // Only include attachments from accessible channels
                $channelQuery->where(function ($q) use ($request) {
                    $q->where('type', 'public')
                      ->orWhereHas('members', function ($memberQuery) use ($request) {
                          $memberQuery->where('user_id', $request->user()->id);
                      });
                });
            });

        if ($channelId) {
            $query->whereHas('message', function ($messageQuery) use ($channelId) {
                $messageQuery->where('channel_id', $channelId);
            });
        }

        if ($fileType) {
            $mimeTypes = $this->getFileTypeMimeTypes($fileType);
            if ($mimeTypes) {
                $query->whereIn('mime_type', $mimeTypes);
            }
        }

        $attachments = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($attachments);
    }

    private function userCanAccessAttachment(Attachment $attachment, $user): bool
    {
        if (!$attachment->message) {
            return $attachment->uploader_id === $user->id;
        }

        $channel = $attachment->message->channel;

        if ($channel->organization_id !== $user->organization_id) {
            return false;
        }

        if ($channel->type === 'public') {
            return true;
        }

        return $channel->members()->where('user_id', $user->id)->exists();
    }

    private function userCanDeleteAttachment(Attachment $attachment, $user): bool
    {
        if ($attachment->uploader_id === $user->id) {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($attachment->message) {
            $channel = $attachment->message->channel;
            return $channel->members()
                ->where('user_id', $user->id)
                ->where('role', 'admin')
                ->exists();
        }

        return false;
    }

    private function getFileTypeMimeTypes(string $fileType): array
    {
        $mimeTypesMap = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ],
            'video' => ['video/mp4', 'video/webm', 'video/quicktime'],
            'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'],
        ];

        return $mimeTypesMap[$fileType] ?? [];
    }
}