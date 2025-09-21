<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:255'],
            'type' => ['sometimes', 'in:messages,channels,users,files,all'],
            'channel_id' => [
                'sometimes',
                'integer',
                'exists:channels,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $channel = \App\Models\Channel::find($value);
                        if (!$channel) {
                            return;
                        }

                        // Check if user can access this channel
                        if ($channel->type === 'public') {
                            if ($channel->organization_id !== $this->user()->organization_id) {
                                $fail('You do not have access to this channel.');
                            }
                        } else {
                            if (!$channel->members()->where('user_id', $this->user()->id)->exists()) {
                                $fail('You do not have access to this channel.');
                            }
                        }
                    }
                }
            ],
            'sender_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $user = \App\Models\User::find($value);
                        if ($user && $user->organization_id !== $this->user()->organization_id) {
                            $fail('You can only search for users in your organization.');
                        }
                    }
                }
            ],
            'file_type' => ['sometimes', 'in:image,document,video,audio'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'has_attachments' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Search query is required.',
            'q.min' => 'Search query must be at least 1 character.',
            'q.max' => 'Search query cannot exceed 255 characters.',
            'type.in' => 'Invalid search type. Must be one of: messages, channels, users, files, all.',
            'channel_id.exists' => 'The specified channel does not exist.',
            'sender_id.exists' => 'The specified user does not exist.',
            'file_type.in' => 'Invalid file type. Must be one of: image, document, video, audio.',
            'date_to.after_or_equal' => 'End date must be on or after start date.',
            'limit.min' => 'Limit must be at least 1.',
            'limit.max' => 'Limit cannot exceed 100.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('q')) {
            $this->merge([
                'q' => trim($this->q)
            ]);
        }

        // Set default limit if not provided
        if (!$this->has('limit')) {
            $this->merge(['limit' => 50]);
        }

        // Set default type if not provided
        if (!$this->has('type')) {
            $this->merge(['type' => 'all']);
        }
    }
}