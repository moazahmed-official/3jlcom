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
            'phone' => ['required', 'string', 'exists:users,phone'],
            'code' => ['required', 'string', 'size:6']
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'The phone field is required.',
            'phone.exists' => 'No user found with this phone number.',
            'code.required' => 'The verification code is required.',
            'code.size' => 'The verification code must be 6 digits.'
        ];
    }
}