<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:jpeg,jpg,png,gif,webp,mp4,mov,avi,wmv'
            ],
            'purpose' => [
                'nullable',
                'string',
                // Accept frontend 'other' and normalize in prepareForValidation
                'in:ad,profile,general,brand,model,other'
            ],
            'related_resource' => [
                'nullable',
                'string',
                'max:255'
            ],
            'related_id' => [
                'nullable',
                'integer',
                'min:1'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A file is required for upload.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.max' => 'The file size cannot exceed 10MB.',
            'file.mimes' => 'The file must be an image (jpeg, jpg, png, gif, webp) or video (mp4, mov, avi, wmv).',
            'purpose.in' => 'Purpose must be one of: ad, profile, general, brand, model.',
            'related_id.min' => 'Related ID must be a positive integer.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $purpose = $this->input('purpose');
        \Log::info('StoreMediaRequest (root) prepareForValidation', ['original_purpose' => $purpose]);
        if (is_string($purpose) && strtolower($purpose) === 'other') {
            $this->merge(['purpose' => 'general']);
        }
    }
}