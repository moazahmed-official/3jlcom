<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ads';

    protected $fillable = [
        'user_id', 'type', 'title', 'description', 'category_id', 'brand_id', 'model_id', 
        'city_id', 'country_id', 'year', 'status', 'views_count', 'contact_count',
        'contact_phone', 'whatsapp_number', 'media_count', 'period_days', 'is_pushed_facebook',
        'published_at', 'expired_at', 'archived_at'
    ];

    protected $casts = [
        'views_count' => 'integer',
        'contact_count' => 'integer',
        'year' => 'integer',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function normalAd()
    {
        return $this->hasOne(NormalAd::class, 'ad_id');
    }

    public function uniqueAd()
    {
        return $this->hasOne(UniqueAd::class, 'ad_id');
    }

    public function caishhaAd()
    {
        return $this->hasOne(CaishhaAd::class, 'ad_id');
    }

    public function auction()
    {
        return $this->hasOne(Auction::class, 'ad_id');
    }

    public function media()
    {
        return $this->belongsToMany(Media::class, 'ad_media', 'ad_id', 'media_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function model()
    {
        return $this->belongsTo(CarModel::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get ad specifications with pivot values.
     */
    public function adSpecifications()
    {
        return $this->hasMany(AdSpecification::class, 'ad_id');
    }

    /**
     * Get specifications with values (many-to-many).
     */
    public function specifications()
    {
        return $this->belongsToMany(
            Specification::class,
            'ad_specifications',
            'ad_id',
            'specification_id'
        )->withPivot('value')->withTimestamps();
    }

    /**
     * Get reviews for this ad.
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class, 'ad_id');
    }

    /**
     * Get reports for this ad.
     */
    public function reports()
    {
        return $this->morphMany(\App\Models\Report::class, 'target');
    }

    /**
     * Get upgrade requests for this ad.
     */
    public function upgradeRequests()
    {
        return $this->hasMany(AdUpgradeRequest::class, 'ad_id');
    }

    /**
     * Get pending upgrade request for this ad.
     */
    public function pendingUpgradeRequest()
    {
        return $this->hasOne(AdUpgradeRequest::class, 'ad_id')
            ->where('status', 'pending')
            ->latest();
    }

    /**
     * Get the average rating for this ad.
     */
    public function getAverageRatingAttribute(): float
    {
        return (float) $this->avg_rating;
    }

    /**
     * Get the total reviews count for this ad.
     */
    public function getTotalReviewsAttribute(): int
    {
        return (int) $this->reviews_count;
    }

    /**
     * Scope to order ads by priority based on type and unique ad type definition.
     * Lower priority number = higher priority (shown first).
     * 
     * Priority hierarchy:
     * - Unique ads with type definition: Use type's priority
     * - Auction ads: Default priority 300
     * - Caishha ads: Default priority 200
     * - Findit ads: Default priority 100
     * - Unique ads without type: Default priority 500
     * - Normal ads: Default priority 1000
     */
    public function scopeOrderByPriority($query)
    {
        return $query->leftJoin('unique_ads', 'ads.id', '=', 'unique_ads.ad_id')
            ->leftJoin('unique_ad_type_definitions', 'unique_ads.unique_ad_type_id', '=', 'unique_ad_type_definitions.id')
            ->select('ads.*')
            ->selectRaw('
                CASE 
                    WHEN ads.type = "unique" AND unique_ad_type_definitions.priority IS NOT NULL 
                        THEN unique_ad_type_definitions.priority
                    WHEN ads.type = "auction" THEN 300
                    WHEN ads.type = "caishha" THEN 200
                    WHEN ads.type = "findit" THEN 100
                    WHEN ads.type = "unique" THEN 500
                    WHEN ads.type = "normal" THEN 1000
                    ELSE 9999
                END as calculated_priority
            ')
            ->orderBy('calculated_priority', 'asc')
            ->orderBy('ads.created_at', 'desc');
    }
}
