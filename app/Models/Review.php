<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'stars',
        'user_id',
        'seller_id',
        'ad_id',
    ];

    protected $casts = [
        'stars' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'stars' => 5,
    ];

    /**
     * Get the user who created the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the seller being reviewed (if reviewing a seller)
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the ad being reviewed (if reviewing an ad)
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    /**
     * Get the reviewable entity (polymorphic)
     * This method determines if it's reviewing a seller or ad
     */
    public function getReviewableAttribute()
    {
        if ($this->ad_id) {
            return $this->ad;
        }
        
        if ($this->seller_id) {
            return $this->seller;
        }
        
        return null;
    }

    /**
     * Get the target type (seller or ad)
     */
    public function getTargetTypeAttribute(): string
    {
        if ($this->ad_id) {
            return 'ad';
        }
        
        if ($this->seller_id) {
            return 'seller';
        }
        
        return 'unknown';
    }

    /**
     * Get the target ID
     */
    public function getTargetIdAttribute(): ?int
    {
        return $this->ad_id ?? $this->seller_id;
    }

    /**
     * Scope to filter by rating
     */
    public function scopeByRating(Builder $query, int $stars): Builder
    {
        return $query->where('stars', $stars);
    }

    /**
     * Scope to filter by minimum rating
     */
    public function scopeMinRating(Builder $query, int $minStars): Builder
    {
        return $query->where('stars', '>=', $minStars);
    }

    /**
     * Scope to get high-rated reviews (4-5 stars)
     */
    public function scopeHighRated(Builder $query): Builder
    {
        return $query->where('stars', '>=', 4);
    }

    /**
     * Scope to get low-rated reviews (1-2 stars)
     */
    public function scopeLowRated(Builder $query): Builder
    {
        return $query->where('stars', '<=', 2);
    }

    /**
     * Scope to get reviews for a specific ad
     */
    public function scopeForAd(Builder $query, int $adId): Builder
    {
        return $query->where('ad_id', $adId);
    }

    /**
     * Scope to get reviews for a specific seller
     */
    public function scopeForSeller(Builder $query, int $sellerId): Builder
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope to get reviews by a specific user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent reviews
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if the review is high-rated
     */
    public function isHighRated(): bool
    {
        return $this->stars >= 4;
    }

    /**
     * Check if the review is low-rated
     */
    public function isLowRated(): bool
    {
        return $this->stars <= 2;
    }
}
