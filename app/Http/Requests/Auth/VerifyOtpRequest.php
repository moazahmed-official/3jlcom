<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            // Accept either phone or email (one is required)
            'phone' => ['required_without:email', 'nullable', 'string', 'exists:users,phone'],
            'email' => ['required_without:phone', 'nullable', 'email', 'exists:users,email'],
            'code' => ['required', 'string', 'size:6']
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without' => 'Either phone or email is required.',
            'phone.exists' => 'No user found with this phone number.',
            'email.required_without' => 'Either email or phone is required.',
            'email.exists' => 'No user found with this email address.',
            'code.required' => 'The verification code is required.',
            'code.size' => 'The verification code must be 6 digits.'
        ];
    }
}