<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country_id',
        'city_id',
        'account_type',
        'profile_image_id',
        'is_verified',
        'seller_verified',
        'seller_verified_at',
        'email_verified_at',
        'otp',
        'otp_expires_at',
    ];

    // ========================================
    // PACKAGE RELATIONSHIPS
    // ========================================

    /**
     * Get user's package subscriptions (pivot records).
     */
    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }

    /**
     * Get all packages this user has (through pivot).
     */
    public function packages()
    {
        return $this->belongsToMany(Package::class, 'user_packages')
            ->withPivot(['start_date', 'end_date', 'active'])
            ->withTimestamps();
    }

    /**
     * Get user's active packages.
     */
    public function activePackages()
    {
        return $this->packages()
            ->wherePivot('active', true)
            ->where(function ($query) {
                $query->whereNull('user_packages.end_date')
                    ->orWhere('user_packages.end_date', '>=', now()->toDateString());
            });
    }

    /**
     * Get user's current active package (most recently assigned active one).
     */
    public function activePackage()
    {
        return $this->hasOneThrough(
            Package::class,
            UserPackage::class,
            'user_id',
            'id',
            'id',
            'package_id'
        )->where('user_packages.active', true)
            ->where(function ($query) {
                $query->whereNull('user_packages.end_date')
                    ->orWhere('user_packages.end_date', '>=', now()->toDateString());
            })
            ->latest('user_packages.created_at');
    }

    /**
     * Get the roles associated with the user.
     */
    public function roles()
    {
        return $this->belongsToMany(\App\Models\Role::class, 'user_role');
    }

    /**
     * Get the seller verification requests for the user.
     */
    public function sellerVerificationRequests()
    {
        return $this->hasMany(SellerVerificationRequest::class);
    }

    /**
     * Get the latest seller verification request for the user.
     */
    public function latestSellerVerificationRequest()
    {
        return $this->hasOne(SellerVerificationRequest::class)->latest();
    }

    /**
     * Get the user's favorited ads.
     */
    public function favorites()
    {
        return $this->belongsToMany(Ad::class, 'user_favorites', 'user_id', 'ad_id')->withTimestamps();
    }

    /**
     * Alias for favorites() - Get the user's favorited ads.
     */
    public function favoriteAds()
    {
        return $this->favorites();
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        // Super-admin should have all roles implicitly
        if ($this->roles()->where('name', 'super-admin')->exists()) {
            return true;
        }

        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if the user has any of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        // Super-admin should have all roles implicitly
        if ($this->roles()->where('name', 'super-admin')->exists()) {
            return true;
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        if (!$this->roles()->where('role_id', $role->id)->exists()) {
            $this->roles()->attach($role);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role);
        }
    }

    /**
     * Check if the user is an admin (admin or super-admin).
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Check if the user is a dealer or showroom.
     */
    public function isDealerOrShowroom(): bool
    {
        // Check both roles and account_type
        return $this->hasAnyRole(['dealer', 'showroom']) 
            || in_array($this->account_type, ['dealer', 'showroom']);
    }

    /**
     * Get the user's submitted Caishha offers.
     */
    public function caishhaOffers()
    {
        return $this->hasMany(\App\Models\CaishhaOffer::class, 'user_id');
    }

    /**
     * Check if user can submit offer on a Caishha ad.
     * During dealer window: Only dealers and verified sellers can submit.
     * After dealer window: All users (dealers, sellers, individuals) can submit.
     * Seller offers are hidden from ad owner until visibility period expires.
     */
    public function canSubmitCaishhaOffer(\App\Models\CaishhaAd $caishhaAd): bool
    {
        // Cannot submit if ad cannot accept offers
        if (!$caishhaAd->canAcceptOffers()) {
            return false;
        }

        // Check if user already has an offer (allow update, but not duplicate)
        $existingOffer = $caishhaAd->offers()->where('user_id', $this->id)->first();
        // If they already have an offer, they cannot submit a new one (must update existing)
        // This method is for initial submission check; update handled separately
        
        // During dealer window: only dealers and verified sellers
        if ($caishhaAd->isInDealerWindow()) {
            return $this->isDealerOrShowroom() || $this->seller_verified;
        }
        
        // After dealer window (individual window): everyone can submit
        return $caishhaAd->isInIndividualWindow();
    }

    /**
     * Get the user's FindIt requests.
     */
    public function finditRequests()
    {
        return $this->hasMany(\App\Models\FinditRequest::class, 'user_id');
    }

    /**
     * Get reviews created by this user.
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class, 'user_id');
    }

    /**
     * Get reviews received by this user (as a seller).
     */
    public function reviewsReceived()
    {
        return $this->hasMany(\App\Models\Review::class, 'seller_id');
    }

    /**
     * Get reports created by this user.
     */
    public function reports()
    {
        return $this->hasMany(\App\Models\Report::class, 'reported_by_user_id');
    }

    /**
     * Get reports received by this user (user being reported).
     */
    public function reportsReceived()
    {
        return $this->morphMany(\App\Models\Report::class, 'target');
    }

    /**
     * Get reports assigned to this user (as a moderator).
     */
    public function assignedReports()
    {
        return $this->hasMany(\App\Models\Report::class, 'assigned_to');
    }

    /**
     * Get the average rating for this user (as a seller).
     */
    public function getAverageRatingAttribute(): float
    {
        return (float) $this->avg_rating;
    }

    /**
     * Get the total reviews count for this user (as a seller).
     */
    public function getTotalReviewsAttribute(): int
    {
        return (int) $this->reviews_count;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'seller_verified_at' => 'datetime',
            'seller_verified' => 'boolean',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ========================================
    // PACKAGE FEATURE PERMISSION HELPERS
    // ========================================

    /**
     * Get user's current active package with features loaded.
     */
    public function getCurrentPackage(): ?Package
    {
        return $this->activePackages()
            ->with('packageFeatures')
            ->latest('user_packages.created_at')
            ->first();
    }

    /**
     * Get user's current package features.
     */
    public function getCurrentPackageFeatures(): ?PackageFeature
    {
        $package = $this->getCurrentPackage();
        return $package?->packageFeatures;
    }

    /**
     * Check if user has an active package.
     */
    public function hasActivePackage(): bool
    {
        return $this->activePackages()->exists();
    }

    /**
     * Check if user can publish a specific ad type.
     */
    public function canPublishAdType(string $adType): bool
    {
        // Admins can always publish
        if ($this->isAdmin()) {
            return true;
        }

        $package = $this->getCurrentPackage();
        
        if (!$package) {
            // No package: allow only normal ads by default
            return $adType === PackageFeature::AD_TYPE_NORMAL;
        }

        return $package->isAdTypeAllowed($adType);
    }

    /**
     * Get the ad limit for a specific ad type.
     */
    public function getAdTypeLimit(string $adType): ?int
    {
        $package = $this->getCurrentPackage();
        
        if (!$package) {
            return null; // Unlimited for normal ads when no package
        }

        return $package->getAdTypeLimit($adType);
    }

    /**
     * Get remaining ads count for a specific ad type.
     * Returns null if unlimited.
     */
    public function getRemainingAdsForType(string $adType): ?int
    {
        $limit = $this->getAdTypeLimit($adType);
        
        if ($limit === null) {
            return null; // Unlimited
        }

        // Count user's active ads of this type
        $usedCount = $this->countActiveAdsByType($adType);
        
        return max(0, $limit - $usedCount);
    }

    /**
     * Count user's active ads by type.
     */
    public function countActiveAdsByType(string $adType): int
    {
        return Ad::where('user_id', $this->id)
            ->where('type', $adType)
            ->whereIn('status', ['draft', 'pending', 'published'])
            ->count();
    }

    /**
     * Check if user can create more ads of a specific type.
     */
    public function canCreateMoreAds(string $adType): bool
    {
        if (!$this->canPublishAdType($adType)) {
            return false;
        }

        $remaining = $this->getRemainingAdsForType($adType);
        
        // null means unlimited
        return $remaining === null || $remaining > 0;
    }

    /**
     * Check if user has a specific package feature.
     */
    public function hasPackageFeature(string $feature): bool
    {
        $features = $this->getCurrentPackageFeatures();
        
        if (!$features) {
            return false;
        }

        return (bool) $features->{$feature};
    }

    /**
     * Check if user can push ads to Facebook.
     */
    public function canPushToFacebook(): bool
    {
        return $this->getCurrentPackage()?->canPushToFacebook() ?? false;
    }

    /**
     * Check if user's unique ads can auto-republish.
     */
    public function canAutoRepublish(): bool
    {
        return $this->getCurrentPackage()?->canAutoRepublish() ?? false;
    }

    /**
     * Check if user can use banners in ads.
     */
    public function canUseBanner(): bool
    {
        return $this->getCurrentPackage()?->canUseBanner() ?? false;
    }

    /**
     * Check if user can use background colors in ads.
     */
    public function canUseBackgroundColor(): bool
    {
        return $this->getCurrentPackage()?->canUseBackgroundColor() ?? false;
    }

    /**
     * Check if user can feature ads.
     */
    public function canFeatureAds(): bool
    {
        return $this->getCurrentPackage()?->canFeatureAds() ?? false;
    }

    /**
     * Check if user can bulk upload.
     */
    public function canBulkUpload(): bool
    {
        return $this->getCurrentPackage()?->canBulkUpload() ?? false;
    }

    /**
     * Get user's images per ad limit.
     */
    public function getImagesPerAdLimit(): int
    {
        return $this->getCurrentPackage()?->getImagesPerAdLimit() ?? 10;
    }

    /**
     * Get user's videos per ad limit.
     */
    public function getVideosPerAdLimit(): int
    {
        return $this->getCurrentPackage()?->getVideosPerAdLimit() ?? 1;
    }

    /**
     * Get user's default ad duration.
     */
    public function getDefaultAdDuration(): int
    {
        return $this->getCurrentPackage()?->getDefaultAdDuration() ?? 30;
    }

    /**
     * Get user's max ad duration.
     */
    public function getMaxAdDuration(): int
    {
        return $this->getCurrentPackage()?->getMaxAdDuration() ?? 90;
    }

    /**
     * Get complete feature summary for user's current package.
     */
    public function getPackageFeatureSummary(): array
    {
        $package = $this->getCurrentPackage();
        
        if (!$package) {
            return [
                'has_package' => false,
                'package' => null,
                'features' => null,
            ];
        }

        return [
            'has_package' => true,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
            ],
            'features' => $package->getFeatureSummary(),
        ];
    }
}
