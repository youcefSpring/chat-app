<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isAdmin() || $this->user()->id === $this->route('user')->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['teacher', 'admin', 'editor'])],
            'bio' => ['nullable', 'string', 'max:2000'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'contact_info' => ['nullable', 'array'],
            'contact_info.phone' => ['nullable', 'string', 'max:20'],
            'contact_info.address' => ['nullable', 'string', 'max:500'],
            'contact_info.website' => ['nullable', 'url'],
            'contact_info.linkedin' => ['nullable', 'url'],
            'contact_info.twitter' => ['nullable', 'url'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already taken.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'User role is required.',
            'role.in' => 'Invalid user role selected.',
            'profile_picture.image' => 'Profile picture must be an image.',
            'profile_picture.mimes' => 'Profile picture must be a JPG, JPEG, or PNG file.',
            'profile_picture.max' => 'Profile picture must be smaller than 2MB.',
            'bio.max' => 'Bio cannot exceed 2000 characters.',
        ];
    }
}