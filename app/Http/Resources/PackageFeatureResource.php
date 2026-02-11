<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\PackageFeature;

class PackageFeatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package_id' => $this->package_id,
            'configured' => true,
            
            // Ad Types with Permissions & Limits
            'ad_types' => [
                'normal' => [
                    'allowed' => $this->normal_ads_allowed,
                    'limit' => $this->normal_ads_limit,
                    'unlimited' => $this->normal_ads_allowed && $this->normal_ads_limit === null,
                ],
                'unique' => [
                    'allowed' => $this->unique_ads_allowed,
                    'limit' => $this->unique_ads_limit,
                    'unlimited' => $this->unique_ads_allowed && $this->unique_ads_limit === null,
                ],
                'caishha' => [
                    'allowed' => $this->caishha_ads_allowed,
                    'limit' => $this->caishha_ads_limit,
                    'unlimited' => $this->caishha_ads_allowed && $this->caishha_ads_limit === null,
                ],
                'findit' => [
                    'allowed' => $this->findit_ads_allowed,
                    'limit' => $this->findit_ads_limit,
                    'unlimited' => $this->findit_ads_allowed && $this->findit_ads_limit === null,
                ],
                'auction' => [
                    'allowed' => $this->auction_ads_allowed,
                    'limit' => $this->auction_ads_limit,
                    'unlimited' => $this->auction_ads_allowed && $this->auction_ads_limit === null,
                ],
            ],
            
            // Role/User Upgrade Features
            'role_features' => [
                'grants_seller_status' => $this->grants_seller_status,
                'auto_verify_seller' => $this->auto_verify_seller,
                'grants_marketer_status' => $this->grants_marketer_status,
                'grants_verified_badge' => $this->grants_verified_badge,
            ],
            
            // Ad-Level Capabilities
            'ad_capabilities' => [
                'can_push_to_facebook' => $this->can_push_to_facebook,
                'can_auto_republish' => $this->can_auto_republish,
                'can_use_banner' => $this->can_use_banner,
                'can_use_background_color' => $this->can_use_background_color,
                'can_feature_ads' => $this->can_feature_ads,
                'featured_ads_limit' => $this->featured_ads_limit,
                'images_per_ad_limit' => $this->images_per_ad_limit,
                'videos_per_ad_limit' => $this->videos_per_ad_limit,
                'ad_duration_days' => $this->ad_duration_days,
                'max_ad_duration_days' => $this->max_ad_duration_days,
                'show_contact_immediately' => $this->show_contact_immediately,
            ],
            
            // Additional Features
            'additional_features' => [
                'priority_support' => $this->priority_support,
                'advanced_analytics' => $this->advanced_analytics,
                'bulk_upload_allowed' => $this->bulk_upload_allowed,
                'bulk_upload_limit' => $this->bulk_upload_limit,
            ],
            
            // Actionable Feature Credits
            'actionable_features' => [
                'allows_image_frame' => $this->allows_image_frame,
                'caishha_feature_enabled' => $this->caishha_feature_enabled,
                'facebook_push_limit' => $this->facebook_push_limit,
                'carseer_api_credits' => $this->carseer_api_credits,
                'auto_bg_credits' => $this->auto_bg_credits,
                'pixblin_credits' => $this->pixblin_credits,
                'ai_video_credits' => $this->ai_video_credits,
                'custom_features_text' => $this->custom_features_text ?? [],
            ],
            
            // Summary counts for UI display
            'summary' => [
                'allowed_ad_types_count' => $this->countAllowedAdTypes(),
                'allowed_ad_types' => $this->getAllowedAdTypesList(),
                'total_features_enabled' => $this->countEnabledFeatures(),
                'role_upgrades' => $this->getRoleUpgradesList(),
            ],
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Count how many ad types are allowed.
     */
    protected function countAllowedAdTypes(): int
    {
        $count = 0;
        
        if ($this->normal_ads_allowed) $count++;
        if ($this->unique_ads_allowed) $count++;
        if ($this->caishha_ads_allowed) $count++;
        if ($this->findit_ads_allowed) $count++;
        if ($this->auction_ads_allowed) $count++;
        
        return $count;
    }

    /**
     * Get list of allowed ad types.
     */
    protected function getAllowedAdTypesList(): array
    {
        $types = [];
        
        if ($this->normal_ads_allowed) $types[] = 'normal';
        if ($this->unique_ads_allowed) $types[] = 'unique';
        if ($this->caishha_ads_allowed) $types[] = 'caishha';
        if ($this->findit_ads_allowed) $types[] = 'findit';
        if ($this->auction_ads_allowed) $types[] = 'auction';
        
        return $types;
    }

    /**
     * Count total enabled features.
     */
    protected function countEnabledFeatures(): int
    {
        $features = [
            $this->can_push_to_facebook,
            $this->can_auto_republish,
            $this->can_use_banner,
            $this->can_use_background_color,
            $this->can_feature_ads,
            $this->priority_support,
            $this->advanced_analytics,
            $this->bulk_upload_allowed,
            $this->show_contact_immediately,
            $this->grants_seller_status,
            $this->grants_marketer_status,
            $this->grants_verified_badge,
            $this->allows_image_frame,
            $this->caishha_feature_enabled,
            $this->facebook_push_limit > 0,
            $this->ai_video_credits > 0,
            $this->auto_bg_credits > 0,
            $this->pixblin_credits > 0,
            $this->carseer_api_credits > 0,
        ];
        
        return count(array_filter($features));
    }

    /**
     * Get list of role upgrades.
     */
    protected function getRoleUpgradesList(): array
    {
        $roles = [];
        
        if ($this->grants_seller_status) {
            $roles[] = $this->auto_verify_seller ? 'seller (auto-verified)' : 'seller';
        }
        if ($this->grants_marketer_status) {
            $roles[] = 'marketer';
        }
        if ($this->grants_verified_badge) {
            $roles[] = 'verified_badge';
        }
        
        return $roles;
    }
}
