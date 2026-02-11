<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniqueAd extends Model
{
    use HasFactory;

    protected $table = 'unique_ads';

    protected $fillable = [
        'ad_id',
        'unique_ad_type_id',
        'banner_image_id',
        'banner_color',
        'is_auto_republished',
        'applies_caishha_feature',
        'is_verified_ad',
        'is_featured',
        'featured_at',
        'verification_status',
        'verification_requested_at',
        'verified_at',
        'verified_by',
        'verification_rejection_reason',
    ];

    protected $casts = [
        'is_auto_republished' => 'boolean',
        'applies_caishha_feature' => 'boolean',
        'is_verified_ad' => 'boolean',
        'is_featured' => 'boolean',
        'featured_at' => 'datetime',
        'verification_requested_at' => 'datetime',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the base ad record.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Get the banner image media.
     */
    public function bannerImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'banner_image_id');
    }

    /**
     * Get the unique ad type definition for this ad.
     */
    public function typeDefinition(): BelongsTo
    {
        return $this->belongsTo(UniqueAdTypeDefinition::class, 'unique_ad_type_id');
    }

    /**
     * Get the caishha ad data if this unique ad uses caishha feature.
     */
    public function caishhaAd(): BelongsTo
    {
        return $this->belongsTo(CaishhaAd::class, 'ad_id', 'ad_id');
    }
}
