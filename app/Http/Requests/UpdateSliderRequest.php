<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSliderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'image_id' => 'sometimes|required|integer|exists:media,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'value' => 'nullable|string|max:500',
            'order' => 'sometimes|nullable|integer|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Slider name is required',
            'name.max' => 'Slider name cannot exceed 255 characters',
            'image_id.required' => 'Slider image is required',
            'image_id.integer' => 'Image ID must be an integer',
            'image_id.exists' => 'Selected media does not exist',
            'category_id.integer' => 'Category ID must be an integer',
            'category_id.exists' => 'Selected category does not exist',
            'value.max' => 'Value cannot exceed 500 characters',
            'order.integer' => 'Order must be an integer',
            'order.min' => 'Order must be at least 0',
            'status.in' => 'Status must be either active or inactive',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'slider name',
            'image_id' => 'slider image',
            'category_id' => 'category',
            'value' => 'link/value',
            'order' => 'display order',
            'status' => 'slider status',
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
