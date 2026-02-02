<?php

namespace App\Http\Requests\PackageRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewPackageRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
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
            'status.required' => 'Review status is required.',
            'status.in' => 'Status must be either approved or rejected.',
            'admin_notes.max' => 'Admin notes cannot exceed 1000 characters.',
        ];
    }
}
