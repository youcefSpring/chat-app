<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'status' => ['sometimes', 'in:online,offline,away,dnd'],
            'role' => ['sometimes', 'in:admin,member,guest'],
            'settings' => ['sometimes', 'array'],
            'settings.notifications_enabled' => ['sometimes', 'boolean'],
            'settings.email_notifications' => ['sometimes', 'boolean'],
            'settings.push_notifications' => ['sometimes', 'boolean'],
            'settings.theme' => ['sometimes', 'in:light,dark,auto'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Name cannot exceed 255 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'avatar_url.url' => 'Avatar URL must be a valid URL.',
            'avatar_url.max' => 'Avatar URL cannot exceed 500 characters.',
            'status.in' => 'Invalid status. Must be one of: online, offline, away, dnd.',
            'role.in' => 'Invalid role. Must be one of: admin, member, guest.',
            'settings.theme.in' => 'Invalid theme. Must be one of: light, dark, auto.',
        ];
    }
}