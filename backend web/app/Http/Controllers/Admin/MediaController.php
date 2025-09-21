<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'type' => 'required|in:image,document,media'
        ]);

        $file = $request->file('file');
        $type = $request->type;

        $allowedMimes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'document' => ['pdf', 'doc', 'docx', 'txt'],
            'media' => ['mp4', 'mp3', 'avi', 'mov', 'wav']
        ];

        $extension = $file->getClientOriginalExtension();

        if (!in_array(strtolower($extension), $allowedMimes[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type for selected category.'
            ], 400);
        }

        $filename = Str::random(40) . '.' . $extension;
        $path = $file->storeAs("uploads/{$type}s", $filename, 'local');

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'data' => [
                'filename' => $filename,
                'path' => $path,
                'url' => Storage::disk('local')->url($path),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]
        ]);
    }

    public function delete(Request $request, string $file): JsonResponse
    {
        $path = $request->input('path');

        if (!$path || !Storage::disk('local')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.'
            ], 404);
        }

        if (!Str::startsWith($path, 'uploads/')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized file deletion.'
            ], 403);
        }

        Storage::disk('local')->delete($path);

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully.'
        ]);
    }
}