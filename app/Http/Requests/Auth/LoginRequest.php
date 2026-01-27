<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required_without:email', 'string'],
            'email' => ['required_without:phone', 'email'],
            'password' => ['required', 'string'],
            'remember_me' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without' => 'The phone or email field is required.',
            'email.required_without' => 'The phone or email field is required.',
            'email.email' => 'The email must be a valid email address.',
        ];
    }
}
