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
        'banner_image_id',
        'banner_color',
        'is_auto_republished',
        'is_verified_ad',
        'is_featured',
        'featured_at',
    ];

    protected $casts = [
        'is_auto_republished' => 'boolean',
        'is_verified_ad' => 'boolean',
        'is_featured' => 'boolean',
        'featured_at' => 'datetime',
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
}
