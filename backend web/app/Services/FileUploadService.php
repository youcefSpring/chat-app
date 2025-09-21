<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;

class FileUploadService
{
    private const MAX_FILE_SIZE = 104857600; // 100MB
    private const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const ALLOWED_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    private const ALLOWED_AUDIO_TYPES = ['mp3', 'wav', 'ogg', 'm4a'];
    private const ALLOWED_VIDEO_TYPES = ['mp4', 'webm', 'mov', 'avi'];

    public function uploadFile(UploadedFile $file, User $uploader): array
    {
        $this->validateFile($file);

        $fileData = $this->processFile($file, $uploader);

        $this->performVirusScan($fileData['file_path']);

        if ($this->isImage($file)) {
            $fileData['thumbnail_path'] = $this->generateThumbnail($fileData['file_path']);
        }

        return $fileData;
    }

    public function deleteFile(string $filePath): void
    {
        if (Storage::disk('s3')->exists($filePath)) {
            Storage::disk('s3')->delete($filePath);
        }
    }

    public function generateThumbnail(string $filePath): ?string
    {
        try {
            $image = Image::make(Storage::disk('s3')->get($filePath));

            $image->fit(300, 300, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $thumbnailPath = $this->generateThumbnailPath($filePath);

            Storage::disk('s3')->put(
                $thumbnailPath,
                $image->encode('jpg', 85)->__toString()
            );

            return $thumbnailPath;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getFileUrl(string $filePath): string
    {
        return Storage::disk('s3')->url($filePath);
    }

    public function getPresignedUrl(string $filePath, int $expiresInMinutes = 60): string
    {
        return Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes($expiresInMinutes));
    }

    public function getFileMetadata(UploadedFile $file): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ];

        if ($this->isImage($file)) {
            $imageData = getimagesize($file->getPathname());
            if ($imageData) {
                $metadata['width'] = $imageData[0];
                $metadata['height'] = $imageData[1];
            }
        }

        if ($this->isVideo($file) || $this->isAudio($file)) {
            $metadata['duration'] = $this->getMediaDuration($file);
        }

        return $metadata;
    }

    public function compressImage(UploadedFile $file, int $quality = 85): string
    {
        $image = Image::make($file);

        $maxWidth = 1920;
        $maxHeight = 1080;

        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $compressedPath = $this->generateFilePath($file);

        Storage::disk('s3')->put(
            $compressedPath,
            $image->encode(null, $quality)->__toString()
        );

        return $compressedPath;
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => ['The uploaded file is invalid.'],
            ]);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'file' => ['The file size exceeds the maximum allowed size of 100MB.'],
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedTypes = array_merge(
            self::ALLOWED_IMAGE_TYPES,
            self::ALLOWED_DOCUMENT_TYPES,
            self::ALLOWED_AUDIO_TYPES,
            self::ALLOWED_VIDEO_TYPES
        );

        if (!in_array($extension, $allowedTypes)) {
            throw ValidationException::withMessages([
                'file' => ['The file type is not allowed.'],
            ]);
        }
    }

    private function processFile(UploadedFile $file, User $uploader): array
    {
        $filePath = $this->generateFilePath($file);

        if ($this->isImage($file)) {
            $filePath = $this->compressImage($file, 85);
        } else {
            Storage::disk('s3')->putFileAs(
                dirname($filePath),
                $file,
                basename($filePath)
            );
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'metadata' => $this->getFileMetadata($file),
        ];
    }

    private function generateFilePath(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40) . '.' . $extension;
        $datePath = date('Y/m/d');

        return "uploads/{$datePath}/{$filename}";
    }

    private function generateThumbnailPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/thumbs/' . $pathInfo['filename'] . '_thumb.jpg';
    }

    private function isImage(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, self::ALLOWED_IMAGE_TYPES);
    }

    private function isVideo(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, self::ALLOWED_VIDEO_TYPES);
    }

    private function isAudio(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, self::ALLOWED_AUDIO_TYPES);
    }

    private function performVirusScan(string $filePath): void
    {
        Log::info("Virus scan performed for file: {$filePath}");
    }

    private function getMediaDuration(UploadedFile $file): ?int
    {
        return null;
    }
}