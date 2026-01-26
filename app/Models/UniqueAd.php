<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniqueAd extends Model
{
    protected $table = 'unique_ads';

    protected $fillable = [
        'ad_id', 'banner_image_id', 'banner_color', 'is_auto_republished', 'is_verified_ad'
    ];

    protected $casts = [
        'is_auto_republished' => 'boolean',
        'is_verified_ad' => 'boolean',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    public function bannerImage()
    {
        return $this->belongsTo(Media::class, 'banner_image_id');
    }
}
