<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isAdmin() || $this->user()->isTeacher());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:projects,slug'],
            'description' => ['required', 'string', 'max:10000'],
            'live_demo_url' => ['nullable', 'url'],
            'source_code_url' => ['nullable', 'url'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'technologies_used' => ['nullable', 'string', 'max:500'],
            'date_completed' => ['nullable', 'date'],
            'status' => ['required', 'in:active,featured,archived'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['exists:tags,id'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Project title is required.',
            'title.max' => 'Project title cannot exceed 255 characters.',
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'description.required' => 'Project description is required.',
            'description.max' => 'Project description cannot exceed 10,000 characters.',
            'live_demo_url.url' => 'Live demo URL must be a valid URL.',
            'source_code_url.url' => 'Source code URL must be a valid URL.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.mimes' => 'Images must be JPG, JPEG, PNG, or WebP files.',
            'images.*.max' => 'Each image must be smaller than 5MB.',
            'technologies_used.max' => 'Technologies used cannot exceed 500 characters.',
            'date_completed.date' => 'Please provide a valid completion date.',
            'status.required' => 'Project status is required.',
            'status.in' => 'Invalid project status selected.',
            'tag_ids.*.exists' => 'One or more selected tags do not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && $this->title) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title),
            ]);
        }
    }
}