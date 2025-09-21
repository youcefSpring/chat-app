<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'bio' => ['nullable', 'string', 'max:2000'],
            'contact_info' => ['nullable', 'array'],
            'contact_info.phone' => ['nullable', 'string', 'max:20'],
            'contact_info.address' => ['nullable', 'string', 'max:255'],
            'contact_info.linkedin' => ['nullable', 'url', 'max:255'],
            'contact_info.twitter' => ['nullable', 'url', 'max:255'],
            'contact_info.website' => ['nullable', 'url', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'min:8', 'confirmed']
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already taken by another user.',
            'profile_picture.image' => 'The profile picture must be an image.',
            'profile_picture.max' => 'The profile picture must not exceed 2MB.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.min' => 'The new password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.'
        ];
    }
}