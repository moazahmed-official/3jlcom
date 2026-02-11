<?php

namespace App\Http\Requests\UniqueAdType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniqueAdTypeDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:unique_ad_type_definitions,name'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:unique_ad_type_definitions,slug'],
            'display_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'priority' => ['required', 'integer', 'min:1', 'max:10000'],
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
            'max_images' => ['required', 'integer', 'min:1', 'max:100'],
            'max_videos' => ['required', 'integer', 'min:0', 'max:50'],
            
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
