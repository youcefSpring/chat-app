<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $message = $this->route('message');

        if ($message->sender_id !== $this->user()->id) {
            return false;
        }

        $editWindow = 15; // minutes
        return $message->created_at->diffInMinutes(Carbon::now()) <= $editWindow;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Message content is required.',
            'body.max' => 'Message cannot exceed 4000 characters.',
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

    protected function failedAuthorization(): void
    {
        $message = $this->route('message');

        if ($message->sender_id !== $this->user()->id) {
            abort(403, 'You can only edit your own messages.');
        }

        abort(403, 'Messages can only be edited within 15 minutes of sending.');
    }
}