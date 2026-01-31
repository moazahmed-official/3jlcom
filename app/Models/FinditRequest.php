<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class FinditRequest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'findit_requests';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'brand_id',
        'model_id',
        'category_id',
        'min_price',
        'max_price',
        'min_year',
        'max_year',
        'min_mileage',
        'max_mileage',
        'city_id',
        'country_id',
        'transmission',
        'fuel_type',
        'body_type',
        'color',
        'condition',
        'condition_rating',
        'status',
        'media_count',
        'matches_count',
        'expires_at',
        'last_matched_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'min_year' => 'integer',
        'max_year' => 'integer',
        'min_mileage' => 'integer',
        'max_mileage' => 'integer',
        'condition_rating' => 'integer',
        'media_count' => 'integer',
        'matches_count' => 'integer',
        'expires_at' => 'datetime',
        'last_matched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'status' => 'draft',
        'media_count' => 0,
        'matches_count' => 0,
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user who created this request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the brand for this request.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the car model for this request.
     * Named 'carModel' to avoid conflict with Eloquent's model() method.
     */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    /**
     * Get the category for this request.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the city for this request.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country for this request.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the media attached to this request.
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'findit_request_media')
            ->withPivot('order')
            ->orderBy('pivot_order');
    }

    /**
     * Get all matches for this request.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(FinditMatch::class, 'findit_request_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to only active requests.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to requests by a specific user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to requests expiring soon.
     */
    public function scopeExpiring(Builder $query, int $days = 7): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days));
    }

    /**
     * Scope to expired requests.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope to requests in a specific country.
     */
    public function scopeInCountry(Builder $query, int $countryId): Builder
    {
        return $query->where('country_id', $countryId);
    }

    // ==========================================
    // BUSINESS LOGIC
    // ==========================================

    /**
     * Check if this request has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    /**
     * Check if this request is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    /**
     * Activate this request.
     */
    public function activate(int $expiryDays = 30): bool
    {
        $this->status = 'active';
        $this->expires_at = now()->addDays($expiryDays);
        return $this->save();
    }

    /**
     * Close this request.
     */
    public function close(): bool
    {
        $this->status = 'closed';
        return $this->save();
    }

    /**
     * Reactivate a closed or expired request.
     */
    public function reactivate(int $expiryDays = 30): bool
    {
        $this->status = 'active';
        $this->expires_at = now()->addDays($expiryDays);
        return $this->save();
    }

    /**
     * Extend the expiration date.
     */
    public function extend(int $days = 30): bool
    {
        // If already expired, start from now; otherwise add to current expiry
        if ($this->isExpired() || !$this->expires_at) {
            $this->expires_at = now()->addDays($days);
        } else {
            $this->expires_at = $this->expires_at->addDays($days);
        }
        return $this->save();
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): bool
    {
        $this->status = 'expired';
        return $this->save();
    }

    /**
     * Build a query to find matching ads based on the request criteria.
     */
    public function getMatchingAdsQuery(): Builder
    {
        $query = Ad::query()
            ->whereIn('type', ['normal', 'unique'])
            ->where('status', 'published')
            ->where('country_id', $this->country_id);

        // Brand filter
        if ($this->brand_id) {
            $query->where('brand_id', $this->brand_id);
        }

        // Model filter
        if ($this->model_id) {
            $query->where('model_id', $this->model_id);
        }

        // Category filter
        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        // Price range filter - check normal_ads table
        if ($this->min_price || $this->max_price) {
            $query->whereHas('normalAd', function ($q) {
                if ($this->min_price) {
                    $q->where('price_cash', '>=', $this->min_price);
                }
                if ($this->max_price) {
                    $q->where('price_cash', '<=', $this->max_price);
                }
            });
        }

        // Year range filter
        if ($this->min_year) {
            $query->where('year', '>=', $this->min_year);
        }
        if ($this->max_year) {
            $query->where('year', '<=', $this->max_year);
        }

        // City filter (optional - can match any city in country)
        if ($this->city_id) {
            $query->where('city_id', $this->city_id);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Sync media attachments.
     */
    public function syncMedia(array $mediaIds): void
    {
        $syncData = [];
        foreach ($mediaIds as $order => $mediaId) {
            $syncData[$mediaId] = ['order' => $order];
        }
        $this->media()->sync($syncData);
        $this->update(['media_count' => count($mediaIds)]);
    }

    /**
     * Update counters from related tables.
     */
    public function updateCounters(): void
    {
        $this->update([
            'matches_count' => $this->matches()->where('dismissed', false)->count(),
        ]);
    }
}
