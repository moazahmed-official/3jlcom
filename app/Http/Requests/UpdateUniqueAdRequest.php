<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUniqueAdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:10|max:255',
            'description' => 'sometimes|string|min:50|max:5000',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'model_id' => 'nullable|integer|exists:models,id',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'sometimes|nullable|string|max:100',
            'millage' => 'sometimes|nullable|numeric|min:0|max:9999999',
            'contact_phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'banner_image_id' => 'nullable|integer|exists:media,id',
            'banner_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'is_verified_ad' => 'nullable|boolean',
            'is_auto_republished' => 'nullable|boolean',
            'status' => 'sometimes|string|in:draft,published,pending,expired,removed',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'integer|exists:media,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.min' => 'Ad title must be at least 10 characters',
            'title.max' => 'Ad title cannot exceed 255 characters',
            'description.min' => 'Ad description must be at least 50 characters',
            'brand_id.exists' => 'Selected brand does not exist',
            'model_id.exists' => 'Selected model does not exist',
            'year.min' => 'Year must be 1900 or later',
            'year.max' => 'Year cannot be in the far future',
            'banner_image_id.exists' => 'Selected banner image does not exist',
            'banner_color.regex' => 'Banner color must be a valid hex color (e.g., #FF5733)',
            'status.in' => 'Invalid status value',
            'media_ids.*.exists' => 'One or more selected media files do not exist',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}
