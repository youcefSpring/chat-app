<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $organizationId = $this->route('organization')->id;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('organizations', 'name')->ignore($organizationId)
            ],
            'domain' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('organizations', 'domain')->ignore($organizationId)
            ],
            'settings' => ['sometimes', 'array'],
            'settings.max_file_size' => ['sometimes', 'integer', 'min:1', 'max:104857600'],
            'settings.allowed_file_types' => ['sometimes', 'array'],
            'settings.allowed_file_types.*' => ['string', 'in:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,wav,ogg,m4a,mp4,webm,mov,avi'],
            'settings.require_email_verification' => ['sometimes', 'boolean'],
            'settings.enable_2fa' => ['sometimes', 'boolean'],
            'settings.max_channels_per_user' => ['sometimes', 'integer', 'min:1'],
            'settings.max_members_per_channel' => ['sometimes', 'integer', 'min:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This organization name is already taken.',
            'name.max' => 'Organization name cannot exceed 255 characters.',
            'domain.unique' => 'This domain is already associated with another organization.',
            'domain.max' => 'Domain cannot exceed 255 characters.',
            'settings.max_file_size.max' => 'Maximum file size cannot exceed 100MB.',
            'settings.max_file_size.min' => 'Maximum file size must be at least 1 byte.',
            'settings.allowed_file_types.*.in' => 'Invalid file type specified.',
            'settings.max_channels_per_user.min' => 'Maximum channels per user must be at least 1.',
            'settings.max_members_per_channel.min' => 'Maximum members per channel must be at least 2.',
        ];
    }
}