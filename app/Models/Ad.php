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
}
