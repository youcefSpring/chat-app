<?php

namespace App\Http\Requests\Attachment;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:102400', // 100MB in KB
                function ($attribute, $value, $fail) {
                    $allowedMimeTypes = [
                        // Images
                        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                        // Documents
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain',
                        // Audio
                        'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4',
                        // Video
                        'video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo',
                    ];

                    if (!in_array($value->getMimeType(), $allowedMimeTypes)) {
                        $fail('The file type is not allowed.');
                    }

                    // Additional size check based on file type
                    $fileSize = $value->getSize();
                    $mimeType = $value->getMimeType();

                    if (str_starts_with($mimeType, 'video/') && $fileSize > 52428800) { // 50MB for videos
                        $fail('Video files cannot exceed 50MB.');
                    }

                    if (str_starts_with($mimeType, 'image/') && $fileSize > 10485760) { // 10MB for images
                        $fail('Image files cannot exceed 10MB.');
                    }
                },
            ],
            'message_id' => ['sometimes', 'integer', 'exists:messages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File is required.',
            'file.file' => 'The uploaded file is invalid.',
            'file.max' => 'File cannot exceed 100MB.',
            'message_id.exists' => 'The specified message does not exist.',
        ];
    }
}