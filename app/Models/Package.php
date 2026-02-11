<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'features',
        'active',
        'visibility_type',
        'allowed_roles',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'features' => 'array',
        'active' => 'boolean',
        'visibility_type' => 'string',
        'allowed_roles' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'active' => true,
        'price' => 0.00,
        'duration_days' => 30,
        'visibility_type' => 'public',
    ];

    /**
     * Visibility type constants
     */
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_ROLE_BASED = 'role_based';
    public const VISIBILITY_USER_SPECIFIC = 'user_specific';

    /**
     * Package types (for legacy features JSON - deprecated, use PackageFeature)
     * @deprecated Use PackageFeature model instead
     */
    public const FEATURE_ADS_LIMIT = 'ads_limit';
    public const FEATURE_FEATURED_ADS = 'featured_ads';
    public const FEATURE_PRIORITY_SUPPORT = 'priority_support';
    public const FEATURE_ANALYTICS = 'analytics';
    public const FEATURE_BULK_UPLOAD = 'bulk_upload';
    public const FEATURE_VERIFIED_BADGE = 'verified_badge';

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the detailed features for this package (new system).
     */
    public function packageFeatures(): HasOne
    {
        return $this->hasOne(PackageFeature::class);
    }

    /**
     * Get all user packages for this package
     */
    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }

    /**
     * Get users who have this package (through pivot)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_packages')
            ->withPivot(['start_date', 'end_date', 'active'])
            ->withTimestamps();
    }

    /**
     * Get the unique ad types allowed by this package.
     */
    public function uniqueAdTypes()
    {
        return $this->belongsToMany(UniqueAdTypeDefinition::class, 'package_unique_ad_types')
            ->withPivot('ads_limit')
            ->withTimestamps();
    }

    /**
     * Get users who have explicit access to this package (for user_specific visibility).
     */
    public function userAccess()
    {
        return $this->belongsToMany(User::class, 'package_user_access')
            ->withTimestamps();
    }

    /**
     * Scope to get only active packages
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get packages by price range
     */
    public function scopePriceRange(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope to get free packages
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where('price', 0);
    }

    /**
     * Scope to get paid packages
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('price', '>', 0);
    }

    /**
     * Scope to get packages visible to a specific user based on visibility rules.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            // Public packages
            $q->where('visibility_type', self::VISIBILITY_PUBLIC)
              // Role-based packages matching user's role
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('visibility_type', self::VISIBILITY_ROLE_BASED)
                     ->whereJsonContains('allowed_roles', $user->role);
              })
              // User-specific packages where user has explicit access
              ->orWhere(function ($q3) use ($user) {
                  $q3->where('visibility_type', self::VISIBILITY_USER_SPECIFIC)
                     ->whereHas('userAccess', function ($q4) use ($user) {
                         $q4->where('user_id', $user->id);
                     });
              });
        });
    }

    /**
     * Scope to get public packages only.
     */
    public function scopePublicOnly(Builder $query): Builder
    {
        return $query->where('visibility_type', self::VISIBILITY_PUBLIC);
    }

    /**
     * Scope to get role-based packages.
     */
    public function scopeRoleBased(Builder $query): Builder
    {
        return $query->where('visibility_type', self::VISIBILITY_ROLE_BASED);
    }

    /**
     * Scope to get user-specific packages.
     */
    public function scopeUserSpecific(Builder $query): Builder
    {
        return $query->where('visibility_type', self::VISIBILITY_USER_SPECIFIC);
    }

    /**
     * Check if the package is free
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * Check if the package is active
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    // ========================================
    // VISIBILITY HELPERS
    // ========================================

    /**
     * Check if package is public (visible to everyone).
     */
    public function isPublic(): bool
    {
        return $this->visibility_type === self::VISIBILITY_PUBLIC;
    }

    /**
     * Check if package is role-based.
     */
    public function isRoleBased(): bool
    {
        return $this->visibility_type === self::VISIBILITY_ROLE_BASED;
    }

    /**
     * Check if package is user-specific.
     */
    public function isUserSpecific(): bool
    {
        return $this->visibility_type === self::VISIBILITY_USER_SPECIFIC;
    }

    /**
     * Check if a user can see this package based on visibility rules.
     */
    public function isVisibleTo(User $user): bool
    {
        // Public packages visible to all
        if ($this->isPublic()) {
            return true;
        }

        // Role-based packages
        if ($this->isRoleBased()) {
            return in_array($user->role, $this->allowed_roles ?? []);
        }

        // User-specific packages
        if ($this->isUserSpecific()) {
            return $this->userAccess()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Grant access to specific users (for user_specific visibility).
     */
    public function grantAccessToUsers(array $userIds): void
    {
        $this->userAccess()->syncWithoutDetaching($userIds);
    }

    /**
     * Revoke access from specific users.
     */
    public function revokeAccessFromUsers(array $userIds): void
    {
        $this->userAccess()->detach($userIds);
    }

    /**
     * Get a specific feature value
     */
    public function getFeature(string $key, $default = null)
    {
        return $this->features[$key] ?? $default;
    }

    /**
     * Check if package has a specific feature
     */
    public function hasFeature(string $key): bool
    {
        return isset($this->features[$key]) && $this->features[$key];
    }

    /**
     * Get the ads limit for this package
     */
    public function getAdsLimit(): ?int
    {
        return $this->getFeature(self::FEATURE_ADS_LIMIT);
    }

    /**
     * Get the featured ads count for this package
     */
    public function getFeaturedAdsCount(): int
    {
        return $this->getFeature(self::FEATURE_FEATURED_ADS, 0);
    }

    /**
     * Get the number of active subscribers
     */
    public function getActiveSubscribersCountAttribute(): int
    {
        return $this->userPackages()->where('active', true)->count();
    }

    // ========================================
    // PACKAGE FEATURES HELPERS (NEW SYSTEM)
    // ========================================

    /**
     * Get or create package features.
     */
    public function getOrCreateFeatures(): PackageFeature
    {
        if (!$this->packageFeatures) {
            return PackageFeature::createDefaultForPackage($this->id);
        }
        
        return $this->packageFeatures;
    }

    /**
     * Check if this package has configured features.
     */
    public function hasConfiguredFeatures(): bool
    {
        return $this->packageFeatures()->exists();
    }

    /**
     * Check if a specific ad type is allowed by this package.
     */
    public function isAdTypeAllowed(string $adType): bool
    {
        $features = $this->packageFeatures;
        
        if (!$features) {
            // Default: only normal ads allowed if no features configured
            return $adType === PackageFeature::AD_TYPE_NORMAL;
        }
        
        return $features->isAdTypeAllowed($adType);
    }

    /**
     * Get the limit for a specific ad type.
     */
    public function getAdTypeLimit(string $adType): ?int
    {
        $features = $this->packageFeatures;
        
        if (!$features) {
            return null;
        }
        
        return $features->getAdTypeLimit($adType);
    }

    /**
     * Get all allowed ad types for this package.
     */
    public function getAllowedAdTypes(): array
    {
        $features = $this->packageFeatures;
        
        if (!$features) {
            return [PackageFeature::AD_TYPE_NORMAL];
        }
        
        return $features->getAllowedAdTypes();
    }

    /**
     * Check if this package grants seller status.
     */
    public function grantsSeller(): bool
    {
        return $this->packageFeatures?->grantsSeller() ?? false;
    }

    /**
     * Check if this package grants marketer status.
     */
    public function grantsMarketer(): bool
    {
        return $this->packageFeatures?->grantsMarketer() ?? false;
    }

    /**
     * Check if this package auto-verifies sellers.
     */
    public function autoVerifiesSeller(): bool
    {
        return $this->packageFeatures?->autoVerifiesSeller() ?? false;
    }

    /**
     * Check if this package grants verified badge.
     */
    public function grantsVerifiedBadge(): bool
    {
        return $this->packageFeatures?->grantsVerifiedBadge() ?? false;
    }

    /**
     * Check if ads can be pushed to Facebook.
     */
    public function canPushToFacebook(): bool
    {
        return $this->packageFeatures?->canPushToFacebook() ?? false;
    }

    /**
     * Check if unique ads can auto-republish.
     */
    public function canAutoRepublish(): bool
    {
        return $this->packageFeatures?->canAutoRepublish() ?? false;
    }

    /**
     * Check if ads can have banners.
     */
    public function canUseBanner(): bool
    {
        return $this->packageFeatures?->canUseBanner() ?? false;
    }

    /**
     * Check if ads can have custom background colors.
     */
    public function canUseBackgroundColor(): bool
    {
        return $this->packageFeatures?->canUseBackgroundColor() ?? false;
    }

    /**
     * Check if ads can be featured.
     */
    public function canFeatureAds(): bool
    {
        return $this->packageFeatures?->canFeatureAds() ?? false;
    }

    /**
     * Check if bulk upload is allowed.
     */
    public function canBulkUpload(): bool
    {
        return $this->packageFeatures?->canBulkUpload() ?? false;
    }

    /**
     * Get images per ad limit.
     */
    public function getImagesPerAdLimit(): int
    {
        return $this->packageFeatures?->images_per_ad_limit ?? 10;
    }

    /**
     * Get videos per ad limit.
     */
    public function getVideosPerAdLimit(): int
    {
        return $this->packageFeatures?->videos_per_ad_limit ?? 1;
    }

    /**
     * Get default ad duration in days.
     */
    public function getDefaultAdDuration(): int
    {
        return $this->packageFeatures?->ad_duration_days ?? 30;
    }

    /**
     * Get max ad duration in days.
     */
    public function getMaxAdDuration(): int
    {
        return $this->packageFeatures?->max_ad_duration_days ?? 90;
    }

    /**
     * Get complete feature summary for this package.
     */
    public function getFeatureSummary(): array
    {
        $features = $this->packageFeatures;
        
        if (!$features) {
            return [
                'ad_types' => [
                    'normal' => ['allowed' => true, 'limit' => null, 'unlimited' => true],
                    'unique' => ['allowed' => false, 'limit' => 0, 'unlimited' => false],
                    'caishha' => ['allowed' => false, 'limit' => 0, 'unlimited' => false],
                    'findit' => ['allowed' => false, 'limit' => 0, 'unlimited' => false],
                    'auction' => ['allowed' => false, 'limit' => 0, 'unlimited' => false],
                ],
                'role_features' => [
                    'grants_seller_status' => false,
                    'auto_verify_seller' => false,
                    'grants_marketer_status' => false,
                    'grants_verified_badge' => false,
                ],
                'ad_capabilities' => [
                    'can_push_to_facebook' => false,
                    'can_auto_republish' => false,
                    'can_use_banner' => false,
                    'can_use_background_color' => false,
                    'can_feature_ads' => false,
                    'featured_ads_limit' => null,
                    'images_per_ad_limit' => 10,
                    'videos_per_ad_limit' => 1,
                    'ad_duration_days' => 30,
                    'max_ad_duration_days' => 90,
                    'show_contact_immediately' => false,
                ],
                'additional_features' => [
                    'priority_support' => false,
                    'advanced_analytics' => false,
                    'bulk_upload_allowed' => false,
                    'bulk_upload_limit' => null,
                ],
            ];
        }
        
        return $features->toFeatureSummary();
    }

    // ========================================
    // UNIQUE AD TYPE PERMISSIONS
    // ========================================

    /**
     * Check if this package allows a specific unique ad type.
     */
    public function allowsUniqueAdType(int $typeId): bool
    {
        // Check if package has specific unique ad types assigned
        $hasSpecificTypes = $this->uniqueAdTypes()->exists();
        
        if ($hasSpecificTypes) {
            // If specific types are assigned, check if this type is one of them
            return $this->uniqueAdTypes()->where('unique_ad_type_id', $typeId)->exists();
        }
        
        // Fall back to generic unique ads permission
        return $this->isAdTypeAllowed(PackageFeature::AD_TYPE_UNIQUE);
    }

    /**
     * Get remaining ads count for a specific unique ad type for the user.
     */
    public function getRemainingAdsForUniqueType(User $user, int $typeId): ?int
    {
        // Get the specific type assignment
        $typeAssignment = $this->uniqueAdTypes()
            ->where('unique_ad_type_id', $typeId)
            ->first();
        
        if ($typeAssignment) {
            $limit = $typeAssignment->pivot->ads_limit;
            
            // NULL means unlimited
            if ($limit === null) {
                return null;
            }
            
            // Count user's active ads of this specific type
            $activeCount = $user->ads()
                ->where('type', 'unique')
                ->where('status', 'published')
                ->whereHas('uniqueAd', function ($query) use ($typeId) {
                    $query->where('unique_ad_type_id', $typeId);
                })
                ->count();
            
            return max(0, $limit - $activeCount);
        }
        
        // Fall back to generic unique ads limit
        $genericLimit = $this->getAdTypeLimit(PackageFeature::AD_TYPE_UNIQUE);
        
        if ($genericLimit === null) {
            return null; // Unlimited
        }
        
        // Count all unique ads
        $activeCount = $user->ads()
            ->where('type', 'unique')
            ->where('status', 'published')
            ->count();
        
        return max(0, $genericLimit - $activeCount);
    }

    /**
     * Get all allowed unique ad type IDs for this package.
     */
    public function getAllowedUniqueAdTypeIds(): array
    {
        return $this->uniqueAdTypes()->pluck('unique_ad_type_id')->toArray();
    }
}
