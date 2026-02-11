<?php

namespace App\Http\Requests\UniqueAdType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniqueAdTypeDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $typeId = $this->route('unique_ad_type');

        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('unique_ad_type_definitions', 'name')->ignore($typeId)],
            'slug' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('unique_ad_type_definitions', 'slug')->ignore($typeId)],
            'display_name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'active' => ['boolean'],
            
            // Feature toggles
            'allows_frame' => ['boolean'],
            'allows_colored_frame' => ['boolean'],
            'allows_image_frame' => ['boolean'],
            'auto_republish_enabled' => ['boolean'],
            'facebook_push_enabled' => ['boolean'],
            'caishha_feature_enabled' => ['boolean'],
            
            // Future API credits
            'carseer_api_credits' => ['nullable', 'integer', 'min:0'],
            'auto_bg_credits' => ['nullable', 'integer', 'min:0'],
            'pixblin_credits' => ['nullable', 'integer', 'min:0'],
            
            // Media limits
            'max_images' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'max_videos' => ['sometimes', 'integer', 'min:0', 'max:50'],
            
            // Custom text features
            'custom_features_text' => ['nullable', 'array'],
            'custom_features_text.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug must be in lowercase with hyphens only (e.g., super-unique-ad)',
            'custom_features_text.*.string' => 'Each custom feature must be a text string',
        ];
    }
}
