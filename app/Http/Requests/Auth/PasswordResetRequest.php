<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'exists:users,phone'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            'code' => ['required', 'string', 'size:6']
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'The phone field is required.',
            'phone.exists' => 'No user found with this phone number.',
            'new_password.required' => 'The new password field is required.',
            'new_password.min' => 'The new password must be at least 8 characters.',
            'new_password.confirmed' => 'The password confirmation does not match.',
            'code.required' => 'The verification code is required.',
            'code.size' => 'The verification code must be 6 digits.'
        ];
    }
}