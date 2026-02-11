<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniqueAdTypeDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'price',
        'priority',
        'active',
        'allows_frame',
        'allows_colored_frame',
        'allows_image_frame',
        'auto_republish_enabled',
        'facebook_push_enabled',
        'caishha_feature_enabled',
        'carseer_api_credits',
        'auto_bg_credits',
        'pixblin_credits',
        'max_images',
        'max_videos',
        'custom_features_text',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'priority' => 'integer',
        'active' => 'boolean',
        'allows_frame' => 'boolean',
        'allows_colored_frame' => 'boolean',
        'allows_image_frame' => 'boolean',
        'auto_republish_enabled' => 'boolean',
        'facebook_push_enabled' => 'boolean',
        'caishha_feature_enabled' => 'boolean',
        'carseer_api_credits' => 'integer',
        'auto_bg_credits' => 'integer',
        'pixblin_credits' => 'integer',
        'max_images' => 'integer',
        'max_videos' => 'integer',
        'custom_features_text' => 'array',
    ];

    /**
     * Get the unique ads using this type definition.
     */
    public function uniqueAds(): HasMany
    {
        return $this->hasMany(UniqueAd::class, 'unique_ad_type_id');
    }

    /**
     * Get the ad upgrade requests for this type.
     */
    public function upgradeRequests(): HasMany
    {
        return $this->hasMany(AdUpgradeRequest::class, 'requested_unique_type_id');
    }

    /**
     * Get the packages that allow this unique ad type.
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_unique_ad_types')
            ->withPivot('ads_limit')
            ->withTimestamps();
    }

    /**
     * Scope to filter only active types.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to order by priority (lower number = higher priority).
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Get count of active ads using this type.
     */
    public function getActiveAdsCountAttribute(): int
    {
        return $this->uniqueAds()
            ->whereHas('ad', function ($query) {
                $query->where('status', 'published');
            })
            ->count();
    }

    /**
     * Check if this type allows a specific feature.
     */
    public function allowsFeature(string $feature): bool
    {
        $featureMap = [
            'frame' => 'allows_frame',
            'colored_frame' => 'allows_colored_frame',
            'image_frame' => 'allows_image_frame',
            'auto_republish' => 'auto_republish_enabled',
            'facebook_push' => 'facebook_push_enabled',
            'caishha' => 'caishha_feature_enabled',
        ];

        return isset($featureMap[$feature]) && $this->{$featureMap[$feature]};
    }
}
