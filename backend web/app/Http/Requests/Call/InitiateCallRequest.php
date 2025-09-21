<?php

namespace App\Http\Requests\Call;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateCallRequest extends FormRequest
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
            'type' => ['sometimes', 'in:audio,video'],
            'participants' => ['sometimes', 'array', 'max:50'],
            'participants.*' => [
                'integer',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id);
                })
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Call type must be either audio or video.',
            'participants.max' => 'You can invite a maximum of 50 participants to a call.',
            'participants.*.exists' => 'One or more participants do not exist in your organization.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $channel = $this->route('channel');

            // Check if there's already an active call in this channel
            $activeCall = $channel->calls()
                ->whereIn('status', ['ringing', 'active'])
                ->exists();

            if ($activeCall) {
                $validator->errors()->add('channel', 'There is already an active call in this channel.');
            }

            // For direct channels, ensure only 2 participants maximum
            if ($channel->type === 'direct' && $this->has('participants') && count($this->participants) > 1) {
                $validator->errors()->add('participants', 'Direct channels can only have 2 participants in a call.');
            }
        });
    }
}