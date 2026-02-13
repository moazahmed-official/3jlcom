<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user has required role for media upload
        $user = $this->user();
        return $user && $user->hasAnyRole(['individual', 'dealer', 'showroom', 'admin']);
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,wmv',
                'max:10240', // 10MB max
            ],
            // accept 'other' from frontend as well; we'll normalize in prepareForValidation
            'purpose' => 'nullable|string|in:ad,profile,general,brand,model,other',
            'related_resource' => 'nullable|string|max:255',
            'related_id' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Prepare the data for validation.
     * Map frontend 'other' purpose to an accepted backend value.
     */
    protected function prepareForValidation(): void
    {
        $purpose = $this->input('purpose');
        \Log::info('StoreMediaRequest prepareForValidation', ['original_purpose' => $purpose]);
        if (is_string($purpose)) {
            $lower = strtolower($purpose);
            if ($lower === 'other') {
                // normalize frontend 'other' to backend 'general'
                $this->merge(['purpose' => 'general']);
            }
        }
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A file is required.',
            'file.file' => 'The uploaded file is not valid.',
            'file.mimes' => 'The file must be an image (jpeg, png, jpg, gif, webp) or video (mp4, mov, avi, wmv).',
            'file.max' => 'The file size must not exceed 10MB.',
            'purpose.in' => 'The purpose must be one of: ad, profile, general, brand, model.',
            'related_id.integer' => 'The related ID must be a valid number.',
        ];
    }
}