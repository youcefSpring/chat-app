<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public form, no authentication required
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Honeypot field for spam protection (should be empty)
            'website' => ['nullable', 'max:0'],
            // reCAPTCHA token if implemented
            'g-recaptcha-response' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Your name is required.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email address cannot exceed 255 characters.',
            'subject.required' => 'Subject is required.',
            'subject.max' => 'Subject cannot exceed 255 characters.',
            'message.required' => 'Message is required.',
            'message.min' => 'Message must be at least 10 characters.',
            'message.max' => 'Message cannot exceed 5000 characters.',
            'website.max' => 'Invalid submission detected.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'g-recaptcha-response' => 'reCAPTCHA',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add IP address and user agent for spam tracking
        $this->merge([
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);

        // Clean up message content
        if ($this->has('message')) {
            $this->merge([
                'message' => trim($this->message),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for spam patterns in message
            if ($this->containsSpamPatterns($this->message)) {
                $validator->errors()->add('message', 'Message contains suspicious content.');
            }
        });
    }

    /**
     * Check if message contains spam patterns.
     */
    private function containsSpamPatterns(string $message): bool
    {
        $spamPatterns = [
            '/\b(viagra|casino|lottery|winner|congratulations)\b/i',
            '/http[s]?:\/\/[^\s]+/i', // URLs in message
            '/\b[\w\.-]+@[\w\.-]+\.\w+\b/', // Email addresses in message
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }
}