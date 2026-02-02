<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageFeatureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by middleware/policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // ========================================
            // AD TYPE PERMISSIONS & LIMITS
            // ========================================
            
            // Normal Ads
            'normal_ads_allowed' => ['sometimes', 'boolean'],
            'normal_ads_limit' => ['nullable', 'integer', 'min:0', 'max:10000'],
            
            // Unique/Featured Ads
            'unique_ads_allowed' => ['sometimes', 'boolean'],
            'unique_ads_limit' => ['nullable', 'integer', 'min:0', 'max:10000'],
            
            // Caishha Ads
            'caishha_ads_allowed' => ['sometimes', 'boolean'],
            'caishha_ads_limit' => ['nullable', 'integer', 'min:0', 'max:10000'],
            
            // FindIt Ads
            'findit_ads_allowed' => ['sometimes', 'boolean'],
            'findit_ads_limit' => ['nullable', 'integer', 'min:0', 'max:10000'],
            
            // Auction Ads
            'auction_ads_allowed' => ['sometimes', 'boolean'],
            'auction_ads_limit' => ['nullable', 'integer', 'min:0', 'max:10000'],
            
            // ========================================
            // ROLE/USER UPGRADE FEATURES
            // ========================================
            
            'grants_seller_status' => ['sometimes', 'boolean'],
            'auto_verify_seller' => ['sometimes', 'boolean'],
            'grants_marketer_status' => ['sometimes', 'boolean'],
            'grants_verified_badge' => ['sometimes', 'boolean'],
            
            // ========================================
            // AD-LEVEL CAPABILITIES
            // ========================================
            
            'can_push_to_facebook' => ['sometimes', 'boolean'],
            'can_auto_republish' => ['sometimes', 'boolean'],
            'can_use_banner' => ['sometimes', 'boolean'],
            'can_use_background_color' => ['sometimes', 'boolean'],
            'can_feature_ads' => ['sometimes', 'boolean'],
            'featured_ads_limit' => ['nullable', 'integer', 'min:0', 'max:1000'],
            
            // ========================================
            // ADDITIONAL CAPABILITIES
            // ========================================
            
            'priority_support' => ['sometimes', 'boolean'],
            'advanced_analytics' => ['sometimes', 'boolean'],
            'bulk_upload_allowed' => ['sometimes', 'boolean'],
            'bulk_upload_limit' => ['nullable', 'integer', 'min:0', 'max:1000'],
            
            // Media limits
            'images_per_ad_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'videos_per_ad_limit' => ['nullable', 'integer', 'min:0', 'max:10'],
            
            // Contact visibility
            'show_contact_immediately' => ['sometimes', 'boolean'],
            
            // Ad duration
            'ad_duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'max_ad_duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'normal_ads_limit.min' => 'Normal ads limit cannot be negative.',
            'normal_ads_limit.max' => 'Normal ads limit cannot exceed 10,000.',
            'unique_ads_limit.min' => 'Unique ads limit cannot be negative.',
            'unique_ads_limit.max' => 'Unique ads limit cannot exceed 10,000.',
            'caishha_ads_limit.min' => 'Caishha ads limit cannot be negative.',
            'caishha_ads_limit.max' => 'Caishha ads limit cannot exceed 10,000.',
            'findit_ads_limit.min' => 'FindIt ads limit cannot be negative.',
            'findit_ads_limit.max' => 'FindIt ads limit cannot exceed 10,000.',
            'auction_ads_limit.min' => 'Auction ads limit cannot be negative.',
            'auction_ads_limit.max' => 'Auction ads limit cannot exceed 10,000.',
            'featured_ads_limit.min' => 'Featured ads limit cannot be negative.',
            'images_per_ad_limit.min' => 'Images per ad must be at least 1.',
            'images_per_ad_limit.max' => 'Images per ad cannot exceed 50.',
            'videos_per_ad_limit.max' => 'Videos per ad cannot exceed 10.',
            'ad_duration_days.min' => 'Ad duration must be at least 1 day.',
            'ad_duration_days.max' => 'Ad duration cannot exceed 365 days.',
            'max_ad_duration_days.min' => 'Max ad duration must be at least 1 day.',
            'max_ad_duration_days.max' => 'Max ad duration cannot exceed 365 days.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'normal_ads_allowed' => 'normal ads permission',
            'normal_ads_limit' => 'normal ads limit',
            'unique_ads_allowed' => 'unique ads permission',
            'unique_ads_limit' => 'unique ads limit',
            'caishha_ads_allowed' => 'caishha ads permission',
            'caishha_ads_limit' => 'caishha ads limit',
            'findit_ads_allowed' => 'findit ads permission',
            'findit_ads_limit' => 'findit ads limit',
            'auction_ads_allowed' => 'auction ads permission',
            'auction_ads_limit' => 'auction ads limit',
            'grants_seller_status' => 'seller status grant',
            'auto_verify_seller' => 'auto-verify seller',
            'grants_marketer_status' => 'marketer status grant',
            'grants_verified_badge' => 'verified badge grant',
            'can_push_to_facebook' => 'Facebook push capability',
            'can_auto_republish' => 'auto-republish capability',
            'can_use_banner' => 'banner capability',
            'can_use_background_color' => 'background color capability',
            'can_feature_ads' => 'feature ads capability',
            'featured_ads_limit' => 'featured ads limit',
            'priority_support' => 'priority support',
            'advanced_analytics' => 'advanced analytics',
            'bulk_upload_allowed' => 'bulk upload permission',
            'bulk_upload_limit' => 'bulk upload limit',
            'images_per_ad_limit' => 'images per ad limit',
            'videos_per_ad_limit' => 'videos per ad limit',
            'show_contact_immediately' => 'show contact immediately',
            'ad_duration_days' => 'default ad duration',
            'max_ad_duration_days' => 'maximum ad duration',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $booleanFields = [
            'normal_ads_allowed',
            'unique_ads_allowed',
            'caishha_ads_allowed',
            'findit_ads_allowed',
            'auction_ads_allowed',
            'grants_seller_status',
            'auto_verify_seller',
            'grants_marketer_status',
            'grants_verified_badge',
            'can_push_to_facebook',
            'can_auto_republish',
            'can_use_banner',
            'can_use_background_color',
            'can_feature_ads',
            'priority_support',
            'advanced_analytics',
            'bulk_upload_allowed',
            'show_contact_immediately',
        ];

        $data = [];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $data[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that max_ad_duration_days >= ad_duration_days
            if ($this->filled('ad_duration_days') && $this->filled('max_ad_duration_days')) {
                if ($this->input('max_ad_duration_days') < $this->input('ad_duration_days')) {
                    $validator->errors()->add(
                        'max_ad_duration_days',
                        'Maximum ad duration must be greater than or equal to default ad duration.'
                    );
                }
            }

            // Validate that auto_verify_seller requires grants_seller_status
            if ($this->input('auto_verify_seller') === true && $this->input('grants_seller_status') !== true) {
                $validator->errors()->add(
                    'auto_verify_seller',
                    'Auto-verify seller requires seller status to be granted.'
                );
            }
        });
    }
}
