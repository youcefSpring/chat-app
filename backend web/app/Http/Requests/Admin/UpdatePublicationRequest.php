<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->isTeacher() ||
            auth()->user()->isAdmin() ||
            auth()->user()->isEditor()
        );
    }

    public function rules(): array
    {
        $publicationId = $this->route('publication')->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:article,book,conference_paper,thesis,report,other'],
            'journal' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'string', 'max:50'],
            'issue' => ['nullable', 'string', 'max:50'],
            'pages' => ['nullable', 'string', 'max:50'],
            'doi' => ['nullable', 'string', 'max:255', Rule::unique('publications', 'doi')->ignore($publicationId)],
            'isbn' => ['nullable', 'string', 'max:20'],
            'publication_date' => ['required', 'date', 'before_or_equal:today'],
            'authors' => ['required', 'string', 'max:500'],
            'abstract' => ['nullable', 'string', 'max:2000'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'citation_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,archived'],
            'publication_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['exists:tags,id']
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The publication title is required.',
            'type.in' => 'Please select a valid publication type.',
            'publication_date.before_or_equal' => 'Publication date cannot be in the future.',
            'doi.unique' => 'A publication with this DOI already exists.',
            'publication_file.mimes' => 'The publication file must be a PDF.',
            'publication_file.max' => 'The publication file must not exceed 20MB.'
        ];
    }
}