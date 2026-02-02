<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'features' => 'array',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'active' => true,
        'price' => 0.00,
        'duration_days' => 30,
    ];

    /**
     * Package types (for features array structure)
     */
    public const FEATURE_ADS_LIMIT = 'ads_limit';
    public const FEATURE_FEATURED_ADS = 'featured_ads';
    public const FEATURE_PRIORITY_SUPPORT = 'priority_support';
    public const FEATURE_ANALYTICS = 'analytics';
    public const FEATURE_BULK_UPLOAD = 'bulk_upload';
    public const FEATURE_VERIFIED_BADGE = 'verified_badge';

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
}
