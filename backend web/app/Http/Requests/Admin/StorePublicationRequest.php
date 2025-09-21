<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicationRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:500'],
            'authors' => ['required', 'string', 'max:500'],
            'journal' => ['nullable', 'string', 'max:255'],
            'conference' => ['nullable', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:2030'],
            'abstract' => ['nullable', 'string', 'max:3000'],
            'publication_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'external_link' => ['nullable', 'url'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Publication title is required.',
            'title.max' => 'Publication title cannot exceed 500 characters.',
            'authors.required' => 'Authors field is required.',
            'authors.max' => 'Authors field cannot exceed 500 characters.',
            'journal.max' => 'Journal name cannot exceed 255 characters.',
            'conference.max' => 'Conference name cannot exceed 255 characters.',
            'year.required' => 'Publication year is required.',
            'year.integer' => 'Publication year must be a valid year.',
            'year.min' => 'Publication year must be 1900 or later.',
            'year.max' => 'Publication year cannot be later than 2030.',
            'abstract.max' => 'Abstract cannot exceed 3000 characters.',
            'publication_file.file' => 'Publication file must be a file.',
            'publication_file.mimes' => 'Publication file must be a PDF.',
            'publication_file.max' => 'Publication file must be smaller than 20MB.',
            'external_link.url' => 'External link must be a valid URL.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'publication_file' => 'publication PDF',
            'external_link' => 'external link',
        ];
    }
}