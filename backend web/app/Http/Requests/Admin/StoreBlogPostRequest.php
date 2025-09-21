<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogPostRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'unique:blog_posts,slug'],
            'content' => ['required', 'string', 'min:100'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'published_at' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,published,archived'],
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
            'title.required' => 'Blog post title is required.',
            'title.max' => 'Blog post title cannot exceed 255 characters.',
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'content.required' => 'Blog post content is required.',
            'content.min' => 'Blog post content must be at least 100 characters.',
            'featured_image.image' => 'Featured image must be an image file.',
            'featured_image.mimes' => 'Featured image must be JPG, JPEG, PNG, or WebP.',
            'featured_image.max' => 'Featured image must be smaller than 5MB.',
            'published_at.date' => 'Please provide a valid publication date.',
            'status.required' => 'Blog post status is required.',
            'status.in' => 'Invalid blog post status selected.',
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

        // Set published_at to now if status is published and no date is set
        if ($this->status === 'published' && empty($this->published_at)) {
            $this->merge([
                'published_at' => now(),
            ]);
        }
    }
}