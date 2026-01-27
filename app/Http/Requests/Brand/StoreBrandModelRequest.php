<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandModelRequest extends FormRequest
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
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'year_from' => 'nullable|integer|min:1900|max:' . (date('Y') + 2),
            'year_to' => 'nullable|integer|min:1900|max:' . (date('Y') + 2) . '|gte:year_from',
        ];
    }

    public function messages(): array
    {
        return [
            'name_en.required' => 'English name is required.',
            'name_ar.required' => 'Arabic name is required.',
            'year_from.min' => 'Year from must be at least 1900.',
            'year_to.gte' => 'Year to must be greater than or equal to year from.',
        ];
    }
}