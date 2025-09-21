<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $channel = $this->route('channel');

        if ($channel->type === 'public') {
            return $channel->organization_id === $this->user()->organization_id;
        }

        return $channel->members()->where('user_id', $this->user()->id)->exists();
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4000'],
            'type' => ['sometimes', 'in:text,system,call_start,call_end'],
            'reply_to_message_id' => [
                'nullable',
                'integer',
                'exists:messages,id',
                Rule::exists('messages', 'id')->where(function ($query) {
                    $channel = $this->route('channel');
                    return $query->where('channel_id', $channel->id);
                })
            ],
            'metadata' => ['sometimes', 'array'],
            'metadata.mentions' => ['sometimes', 'array'],
            'metadata.mentions.*' => [
                'integer',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id);
                })
            ],
            'attachments' => ['sometimes', 'array', 'max:10'],
            'attachments.*.file' => ['required', 'file', 'max:102400'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Message content is required.',
            'body.max' => 'Message cannot exceed 4000 characters.',
            'type.in' => 'Invalid message type.',
            'reply_to_message_id.exists' => 'The message you are replying to does not exist in this channel.',
            'metadata.mentions.*.exists' => 'One or more mentioned users do not exist in your organization.',
            'attachments.max' => 'You can attach a maximum of 10 files per message.',
            'attachments.*.file.required' => 'Attachment file is required.',
            'attachments.*.file.max' => 'Attachment file cannot exceed 100MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('body')) {
            $this->merge([
                'body' => trim($this->body)
            ]);
        }
    }
}