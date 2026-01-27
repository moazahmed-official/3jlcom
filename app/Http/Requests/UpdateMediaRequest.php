<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'related_resource' => [
                'sometimes',
                'nullable',
                'string',
                'max:255'
            ],
            'related_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'related_id.min' => 'Related ID must be a positive integer.',
            'related_resource.max' => 'Related resource cannot exceed 255 characters.',
        ];
    }
}