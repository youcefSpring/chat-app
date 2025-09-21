<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $course = $this->route('course');
        return $this->user() && (
            $this->user()->isAdmin() ||
            ($this->user()->isTeacher() && $course->user_id === $this->user()->id)
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $courseId = $this->route('course')->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', Rule::unique('courses')->ignore($courseId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'syllabus_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['required', 'in:active,archived'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Course title is required.',
            'title.max' => 'Course title cannot exceed 255 characters.',
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'description.max' => 'Course description cannot exceed 5000 characters.',
            'syllabus_file.file' => 'Syllabus must be a file.',
            'syllabus_file.mimes' => 'Syllabus must be a PDF file.',
            'syllabus_file.max' => 'Syllabus file must be smaller than 10MB.',
            'start_date.date' => 'Please provide a valid start date.',
            'end_date.date' => 'Please provide a valid end date.',
            'end_date.after' => 'End date must be after start date.',
            'status.required' => 'Course status is required.',
            'status.in' => 'Invalid course status selected.',
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