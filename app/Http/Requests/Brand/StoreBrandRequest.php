<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'name_en.required' => 'English name is required.',
            'name_en.max' => 'English name cannot exceed 255 characters.',
            'name_ar.required' => 'Arabic name is required.',
            'name_ar.max' => 'Arabic name cannot exceed 255 characters.',
        ];
    }
}