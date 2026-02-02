<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can create packages
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'duration_days' => ['required', 'integer', 'min:0', 'max:3650'], // Max ~10 years
            'features' => ['nullable', 'array'],
            'features.ads_limit' => ['nullable', 'integer', 'min:0'],
            'features.featured_ads' => ['nullable', 'integer', 'min:0'],
            'features.priority_support' => ['nullable', 'boolean'],
            'features.analytics' => ['nullable', 'boolean'],
            'features.bulk_upload' => ['nullable', 'boolean'],
            'features.verified_badge' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Package name is required.',
            'name.max' => 'Package name cannot exceed 255 characters.',
            'price.required' => 'Package price is required.',
            'price.numeric' => 'Package price must be a number.',
            'price.min' => 'Package price cannot be negative.',
            'duration_days.required' => 'Package duration is required.',
            'duration_days.integer' => 'Package duration must be a whole number of days.',
            'duration_days.min' => 'Package duration cannot be negative.',
            'features.array' => 'Features must be an array.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 403,
            'message' => 'You are not authorized to create packages.',
            'errors' => [],
        ], 403));
    }
}
