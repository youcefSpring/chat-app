<?php

namespace App\Http\Requests\Reaction;

use Illuminate\Foundation\Http\FormRequest;

class AddReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $message = $this->route('message');
        $channel = $message->channel;

        if ($channel->type === 'public') {
            return $channel->organization_id === $this->user()->organization_id;
        }

        return $channel->members()->where('user_id', $this->user()->id)->exists();
    }

    public function rules(): array
    {
        return [
            'reaction' => [
                'required',
                'string',
                'max:50',
                'regex:/^[\p{So}\p{Sk}]+$/u'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reaction.required' => 'Reaction is required.',
            'reaction.max' => 'Reaction cannot exceed 50 characters.',
            'reaction.regex' => 'Reaction must be a valid emoji.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reaction')) {
            $this->merge([
                'reaction' => trim($this->reaction)
            ]);
        }
    }
}