<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $channel = $this->route('channel');

        return $this->user()->role === 'admin' ||
               $channel->created_by === $this->user()->id ||
               $channel->members()->where('user_id', $this->user()->id)->where('role', 'admin')->exists();
    }

    public function rules(): array
    {
        $channel = $this->route('channel');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-z0-9-_]+$/',
                Rule::unique('channels', 'name')->where(function ($query) use ($channel) {
                    return $query->where('organization_id', $channel->organization_id)
                                ->where('type', $channel->type);
                })->ignore($channel->id)
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'settings' => ['sometimes', 'array'],
            'settings.notifications_enabled' => ['sometimes', 'boolean'],
            'settings.message_retention_days' => ['sometimes', 'integer', 'min:1', 'max:3650'],
            'settings.allow_file_uploads' => ['sometimes', 'boolean'],
            'settings.max_file_size' => ['sometimes', 'integer', 'min:1', 'max:104857600'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Channel name cannot exceed 100 characters.',
            'name.regex' => 'Channel name can only contain lowercase letters, numbers, hyphens, and underscores.',
            'name.unique' => 'A channel with this name already exists in your organization.',
            'description.max' => 'Channel description cannot exceed 500 characters.',
            'settings.message_retention_days.min' => 'Message retention must be at least 1 day.',
            'settings.message_retention_days.max' => 'Message retention cannot exceed 10 years.',
            'settings.max_file_size.min' => 'Maximum file size must be at least 1 byte.',
            'settings.max_file_size.max' => 'Maximum file size cannot exceed 100MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => strtolower($this->name)
            ]);
        }
    }
}