<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['admin', 'member']);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9-_]+$/',
                Rule::unique('channels', 'name')->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id)
                                ->where('type', $this->input('type', 'public'));
                })
            ],
            'type' => ['required', 'in:public,private,direct'],
            'description' => ['nullable', 'string', 'max:500'],
            'invited_users' => ['sometimes', 'array'],
            'invited_users.*' => [
                'integer',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id);
                })
            ],
            'settings' => ['sometimes', 'array'],
            'settings.notifications_enabled' => ['sometimes', 'boolean'],
            'settings.message_retention_days' => ['sometimes', 'integer', 'min:1', 'max:3650'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Channel name is required.',
            'name.max' => 'Channel name cannot exceed 100 characters.',
            'name.regex' => 'Channel name can only contain lowercase letters, numbers, hyphens, and underscores.',
            'name.unique' => 'A channel with this name already exists in your organization.',
            'type.required' => 'Channel type is required.',
            'type.in' => 'Invalid channel type. Must be one of: public, private, direct.',
            'description.max' => 'Channel description cannot exceed 500 characters.',
            'invited_users.*.exists' => 'One or more invited users do not exist in your organization.',
            'settings.message_retention_days.min' => 'Message retention must be at least 1 day.',
            'settings.message_retention_days.max' => 'Message retention cannot exceed 10 years.',
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