<?php

namespace App\Http\Requests\AdUpgradeRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAdUpgradeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:approved,rejected'],
            'admin_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please specify approval or rejection status',
            'status.in' => 'Status must be either approved or rejected',
            'admin_message.max' => 'Admin message cannot exceed 1000 characters',
        ];
    }
}
