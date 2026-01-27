<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name_en' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'year_from' => 'nullable|integer|min:1900|max:' . (date('Y') + 2),
            'year_to' => 'nullable|integer|min:1900|max:' . (date('Y') + 2) . '|gte:year_from',
        ];
    }
}
