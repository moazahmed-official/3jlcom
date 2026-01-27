<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user has admin role
        $user = $this->user();
        return $user && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:approved,rejected',
            'admin_comments' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Verification status is required.',
            'status.in' => 'Status must be either approved or rejected.',
            'admin_comments.max' => 'Admin comments cannot exceed 1000 characters.',
        ];
    }
}