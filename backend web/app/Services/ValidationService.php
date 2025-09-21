<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;

class ValidationService
{
    public static function validateUserRegistration(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/'
            ],
            'role' => 'sometimes|in:admin,member,guest',
        ], [
            'password.regex' => 'Password must contain at least one lowercase letter, one uppercase letter, and one number.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateChannelCreation(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9-_]+$/'
            ],
            'type' => 'required|in:public,private,direct',
            'description' => 'nullable|string|max:500',
            'invited_users' => 'sometimes|array',
            'invited_users.*' => 'integer|exists:users,id',
        ], [
            'name.regex' => 'Channel name can only contain lowercase letters, numbers, hyphens, and underscores.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateMessage(array $data): array
    {
        $validator = Validator::make($data, [
            'body' => 'required|string|max:4000',
            'type' => 'sometimes|in:text,system,call_start,call_end',
            'reply_to_message_id' => 'nullable|exists:messages,id',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateFileUpload(UploadedFile $file): array
    {
        $validator = Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'max:102400', // 100MB in KB
                function ($attribute, $value, $fail) {
                    $allowedMimeTypes = [
                        // Images
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
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
                },
            ],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateReaction(array $data): array
    {
        $validator = Validator::make($data, [
            'reaction' => [
                'required',
                'string',
                'max:50',
                'regex:/^[\p{So}\p{Sk}]+$/u'
            ],
        ], [
            'reaction.regex' => 'Reaction must be a valid emoji.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateOrganizationSettings(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:255|unique:organizations,name',
            'domain' => 'sometimes|nullable|string|max:255|unique:organizations,domain',
            'settings' => 'sometimes|array',
            'settings.max_file_size' => 'sometimes|integer|min:1|max:104857600',
            'settings.allowed_file_types' => 'sometimes|array',
            'settings.require_email_verification' => 'sometimes|boolean',
            'settings.enable_2fa' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateUserUpdate(array $data, int $userId): array
    {
        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$userId}",
            'avatar_url' => 'sometimes|nullable|url|max:500',
            'status' => 'sometimes|in:online,offline,away,dnd',
            'role' => 'sometimes|in:admin,member,guest',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateCallInitiation(array $data): array
    {
        $validator = Validator::make($data, [
            'type' => 'sometimes|in:audio,video',
            'participants' => 'sometimes|array',
            'participants.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateSearchQuery(array $data): array
    {
        $validator = Validator::make($data, [
            'q' => 'required|string|min:1|max:255',
            'type' => 'sometimes|in:messages,channels,users,files,all',
            'channel_id' => 'sometimes|integer|exists:channels,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validatePassword(string $password): bool
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
    }

    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateChannelName(string $name): bool
    {
        return preg_match('/^[a-z0-9-_]+$/', $name);
    }

    public static function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function validateRateLimits(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $limiter = app(\Illuminate\Cache\RateLimiter::class);

        if ($limiter->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $limiter->hit($key, $decayMinutes * 60);

        return true;
    }

    public static function getRateLimitConfig(): array
    {
        return [
            'login' => ['attempts' => 5, 'decay' => 1], // 5 attempts per minute
            'message' => ['attempts' => 60, 'decay' => 1], // 60 messages per minute
            'file_upload' => ['attempts' => 10, 'decay' => 1], // 10 uploads per minute
            'api_general' => ['attempts' => 1000, 'decay' => 60], // 1000 requests per hour
            'search' => ['attempts' => 30, 'decay' => 1], // 30 searches per minute
        ];
    }

    public static function validateUserPermissions(array $data): array
    {
        $validator = Validator::make($data, [
            'can_create_channels' => 'sometimes|boolean',
            'can_invite_users' => 'sometimes|boolean',
            'can_delete_messages' => 'sometimes|boolean',
            'can_manage_files' => 'sometimes|boolean',
            'can_initiate_calls' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}