<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        
        // Ad type permissions & limits
        'normal_ads_allowed',
        'normal_ads_limit',
        'unique_ads_allowed',
        'unique_ads_limit',
        'caishha_ads_allowed',
        'caishha_ads_limit',
        'findit_ads_allowed',
        'findit_ads_limit',
        'auction_ads_allowed',
        'auction_ads_limit',
        
        // Role/User upgrade features
        'grants_seller_status',
        'auto_verify_seller',
        'grants_marketer_status',
        'grants_verified_badge',
        
        // Ad-level capabilities
        'can_push_to_facebook',
        'can_auto_republish',
        'can_use_banner',
        'can_use_background_color',
        'can_feature_ads',
        'featured_ads_limit',
        
        // Additional capabilities
        'priority_support',
        'advanced_analytics',
        'bulk_upload_allowed',
        'bulk_upload_limit',
        'images_per_ad_limit',
        'videos_per_ad_limit',
        'show_contact_immediately',
        'ad_duration_days',
        'max_ad_duration_days',

        // Actionable feature credits
        'allows_image_frame',
        'caishha_feature_enabled',
        'facebook_push_limit',
        'carseer_api_credits',
        'auto_bg_credits',
        'pixblin_credits',
        'ai_video_credits',
        'custom_features_text',
    ];

    protected $casts = [
        // Ad type permissions
        'normal_ads_allowed' => 'boolean',
        'normal_ads_limit' => 'integer',
        'unique_ads_allowed' => 'boolean',
        'unique_ads_limit' => 'integer',
        'caishha_ads_allowed' => 'boolean',
        'caishha_ads_limit' => 'integer',
        'findit_ads_allowed' => 'boolean',
        'findit_ads_limit' => 'integer',
        'auction_ads_allowed' => 'boolean',
        'auction_ads_limit' => 'integer',
        
        // Role features
        'grants_seller_status' => 'boolean',
        'auto_verify_seller' => 'boolean',
        'grants_marketer_status' => 'boolean',
        'grants_verified_badge' => 'boolean',
        
        // Ad capabilities
        'can_push_to_facebook' => 'boolean',
        'can_auto_republish' => 'boolean',
        'can_use_banner' => 'boolean',
        'can_use_background_color' => 'boolean',
        'can_feature_ads' => 'boolean',
        'featured_ads_limit' => 'integer',
        
        // Additional capabilities
        'priority_support' => 'boolean',
        'advanced_analytics' => 'boolean',
        'bulk_upload_allowed' => 'boolean',
        'bulk_upload_limit' => 'integer',
        'images_per_ad_limit' => 'integer',
        'videos_per_ad_limit' => 'integer',
        'show_contact_immediately' => 'boolean',
        'ad_duration_days' => 'integer',
        'max_ad_duration_days' => 'integer',

        // Actionable feature credits
        'allows_image_frame' => 'boolean',
        'caishha_feature_enabled' => 'boolean',
        'facebook_push_limit' => 'integer',
        'carseer_api_credits' => 'integer',
        'auto_bg_credits' => 'integer',
        'pixblin_credits' => 'integer',
        'ai_video_credits' => 'integer',
        'custom_features_text' => 'array',
        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'normal_ads_allowed' => true,
        'unique_ads_allowed' => false,
        'caishha_ads_allowed' => false,
        'findit_ads_allowed' => false,
        'auction_ads_allowed' => false,
        'grants_seller_status' => false,
        'auto_verify_seller' => false,
        'grants_marketer_status' => false,
        'grants_verified_badge' => false,
        'can_push_to_facebook' => false,
        'can_auto_republish' => false,
        'can_use_banner' => false,
        'can_use_background_color' => false,
        'can_feature_ads' => false,
        'priority_support' => false,
        'advanced_analytics' => false,
        'bulk_upload_allowed' => false,
        'images_per_ad_limit' => 10,
        'videos_per_ad_limit' => 1,
        'show_contact_immediately' => false,
        'ad_duration_days' => 30,
        'max_ad_duration_days' => 90,
        'allows_image_frame' => false,
        'caishha_feature_enabled' => false,
        'facebook_push_limit' => 0,
        'carseer_api_credits' => 0,
        'auto_bg_credits' => 0,
        'pixblin_credits' => 0,
        'ai_video_credits' => 0,
    ];

    /**
     * Ad type constants for consistent referencing
     */
    public const AD_TYPE_NORMAL = 'normal';
    public const AD_TYPE_UNIQUE = 'unique';
    public const AD_TYPE_CAISHHA = 'caishha';
    public const AD_TYPE_FINDIT = 'findit';
    public const AD_TYPE_AUCTION = 'auction';

    /**
     * All supported ad types
     */
    public const AD_TYPES = [
        self::AD_TYPE_NORMAL,
        self::AD_TYPE_UNIQUE,
        self::AD_TYPE_CAISHHA,
        self::AD_TYPE_FINDIT,
        self::AD_TYPE_AUCTION,
    ];

    /**
     * Mapping of ad types to their permission and limit fields
     */
    public const AD_TYPE_FIELDS = [
        self::AD_TYPE_NORMAL => [
            'allowed' => 'normal_ads_allowed',
            'limit' => 'normal_ads_limit',
        ],
        self::AD_TYPE_UNIQUE => [
            'allowed' => 'unique_ads_allowed',
            'limit' => 'unique_ads_limit',
        ],
        self::AD_TYPE_CAISHHA => [
            'allowed' => 'caishha_ads_allowed',
            'limit' => 'caishha_ads_limit',
        ],
        self::AD_TYPE_FINDIT => [
            'allowed' => 'findit_ads_allowed',
            'limit' => 'findit_ads_limit',
        ],
        self::AD_TYPE_AUCTION => [
            'allowed' => 'auction_ads_allowed',
            'limit' => 'auction_ads_limit',
        ],
    ];

    /**
     * Role feature constants
     */
    public const ROLE_SELLER = 'seller';
    public const ROLE_MARKETER = 'marketer';

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the package that owns these features.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    // ========================================
    // AD TYPE PERMISSION METHODS
    // ========================================

    /**
     * Check if a specific ad type is allowed.
     */
    public function isAdTypeAllowed(string $adType): bool
    {
        if (!isset(self::AD_TYPE_FIELDS[$adType])) {
            return false;
        }

        $field = self::AD_TYPE_FIELDS[$adType]['allowed'];
        return (bool) $this->{$field};
    }

    /**
     * Get the limit for a specific ad type.
     * Returns null if unlimited.
     */
    public function getAdTypeLimit(string $adType): ?int
    {
        if (!isset(self::AD_TYPE_FIELDS[$adType])) {
            return 0;
        }

        $field = self::AD_TYPE_FIELDS[$adType]['limit'];
        return $this->{$field};
    }

    /**
     * Get all allowed ad types.
     */
    public function getAllowedAdTypes(): array
    {
        return array_filter(self::AD_TYPES, fn($type) => $this->isAdTypeAllowed($type));
    }

    /**
     * Get ad types with their limits.
     */
    public function getAdTypesWithLimits(): array
    {
        $result = [];
        
        foreach (self::AD_TYPES as $type) {
            $result[$type] = [
                'allowed' => $this->isAdTypeAllowed($type),
                'limit' => $this->getAdTypeLimit($type),
                'unlimited' => $this->isAdTypeAllowed($type) && $this->getAdTypeLimit($type) === null,
            ];
        }
        
        return $result;
    }

    // ========================================
    // ROLE FEATURE METHODS
    // ========================================

    /**
     * Check if this package grants seller status.
     */
    public function grantsSeller(): bool
    {
        return $this->grants_seller_status;
    }

    /**
     * Check if this package grants marketer status.
     */
    public function grantsMarketer(): bool
    {
        return $this->grants_marketer_status;
    }

    /**
     * Check if this package auto-verifies sellers.
     */
    public function autoVerifiesSeller(): bool
    {
        return $this->grants_seller_status && $this->auto_verify_seller;
    }

    /**
     * Check if this package grants verified badge.
     */
    public function grantsVerifiedBadge(): bool
    {
        return $this->grants_verified_badge;
    }

    /**
     * Get all role upgrades this package provides.
     */
    public function getRoleUpgrades(): array
    {
        $roles = [];
        
        if ($this->grants_seller_status) {
            $roles[] = self::ROLE_SELLER;
        }
        
        if ($this->grants_marketer_status) {
            $roles[] = self::ROLE_MARKETER;
        }
        
        return $roles;
    }

    // ========================================
    // AD CAPABILITY METHODS
    // ========================================

    /**
     * Check if ads can be pushed to Facebook.
     */
    public function canPushToFacebook(): bool
    {
        return $this->can_push_to_facebook;
    }

    /**
     * Check if unique ads can auto-republish.
     */
    public function canAutoRepublish(): bool
    {
        return $this->can_auto_republish;
    }

    /**
     * Check if ads can have banners.
     */
    public function canUseBanner(): bool
    {
        return $this->can_use_banner;
    }

    /**
     * Check if ads can have custom background colors.
     */
    public function canUseBackgroundColor(): bool
    {
        return $this->can_use_background_color;
    }

    /**
     * Check if ads can be featured.
     */
    public function canFeatureAds(): bool
    {
        return $this->can_feature_ads;
    }

    /**
     * Get the featured ads limit.
     */
    public function getFeaturedAdsLimit(): ?int
    {
        return $this->featured_ads_limit;
    }

    /**
     * Check if bulk upload is allowed.
     */
    public function canBulkUpload(): bool
    {
        return $this->bulk_upload_allowed;
    }

    /**
     * Get all ad capabilities as an array.
     */
    public function getAdCapabilities(): array
    {
        return [
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
            'allows_image_frame' => $this->allows_image_frame,
            'caishha_feature_enabled' => $this->caishha_feature_enabled,
        ];
    }

    // ========================================
    // ACTIONABLE FEATURE CREDIT METHODS
    // ========================================

    /**
     * Check if image frame feature is allowed.
     */
    public function allowsImageFrame(): bool
    {
        return $this->allows_image_frame;
    }

    /**
     * Check if caishha feature is enabled for this package.
     */
    public function hasCaishhaFeature(): bool
    {
        return $this->caishha_feature_enabled;
    }

    /**
     * Get the total credits for a specific actionable feature.
     */
    public function getFeatureCredits(string $feature): int
    {
        $map = [
            FeatureUsageLog::FEATURE_FACEBOOK_PUSH => 'facebook_push_limit',
            FeatureUsageLog::FEATURE_AI_VIDEO => 'ai_video_credits',
            FeatureUsageLog::FEATURE_AUTO_BG => 'auto_bg_credits',
            FeatureUsageLog::FEATURE_PIXBLIN => 'pixblin_credits',
            FeatureUsageLog::FEATURE_CARSEER => 'carseer_api_credits',
        ];

        return $this->{$map[$feature] ?? null} ?? 0;
    }

    /**
     * Get all actionable feature credits.
     */
    public function getActionableFeatureCredits(): array
    {
        return [
            'facebook_push_limit' => $this->facebook_push_limit,
            'ai_video_credits' => $this->ai_video_credits,
            'auto_bg_credits' => $this->auto_bg_credits,
            'pixblin_credits' => $this->pixblin_credits,
            'carseer_api_credits' => $this->carseer_api_credits,
            'allows_image_frame' => $this->allows_image_frame,
            'caishha_feature_enabled' => $this->caishha_feature_enabled,
            'custom_features_text' => $this->custom_features_text,
        ];
    }

    // ========================================
    // ADDITIONAL CAPABILITY METHODS
    // ========================================

    /**
     * Check if priority support is included.
     */
    public function hasPrioritySupport(): bool
    {
        return $this->priority_support;
    }

    /**
     * Check if advanced analytics are included.
     */
    public function hasAdvancedAnalytics(): bool
    {
        return $this->advanced_analytics;
    }

    /**
     * Get media limits.
     */
    public function getMediaLimits(): array
    {
        return [
            'images_per_ad' => $this->images_per_ad_limit,
            'videos_per_ad' => $this->videos_per_ad_limit,
        ];
    }

    /**
     * Get ad duration settings.
     */
    public function getAdDurationSettings(): array
    {
        return [
            'default_days' => $this->ad_duration_days,
            'max_days' => $this->max_ad_duration_days,
        ];
    }

    // ========================================
    // SUMMARY METHODS
    // ========================================

    /**
     * Get a complete summary of all features.
     */
    public function toFeatureSummary(): array
    {
        return [
            'ad_types' => $this->getAdTypesWithLimits(),
            'role_features' => [
                'grants_seller_status' => $this->grants_seller_status,
                'auto_verify_seller' => $this->auto_verify_seller,
                'grants_marketer_status' => $this->grants_marketer_status,
                'grants_verified_badge' => $this->grants_verified_badge,
            ],
            'ad_capabilities' => $this->getAdCapabilities(),
            'actionable_features' => $this->getActionableFeatureCredits(),
            'additional_features' => [
                'priority_support' => $this->priority_support,
                'advanced_analytics' => $this->advanced_analytics,
                'bulk_upload_allowed' => $this->bulk_upload_allowed,
                'bulk_upload_limit' => $this->bulk_upload_limit,
            ],
        ];
    }

    /**
     * Create default features for a package.
     */
    public static function createDefaultForPackage(int $packageId): self
    {
        return self::create([
            'package_id' => $packageId,
            // Uses default attribute values
        ]);
    }
}
