<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'account_type' => ['sometimes', 'string', 'in:individual,dealer,showroom']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.unique' => 'This email address is already registered.',
            'phone.required' => 'The phone field is required.',
            'phone.unique' => 'This phone number is already registered.',
            'country_id.required' => 'The country field is required.',
            'country_id.exists' => 'The selected country does not exist.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.'
        ];
    }
}