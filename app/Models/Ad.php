<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ad extends Model
{
    use SoftDeletes;

    protected $table = 'ads';

    protected $fillable = [
        'user_id', 'type', 'title', 'description', 'category_id', 'brand_id', 'model_id', 'city_id', 'country_id', 'year', 'status', 'views_count'
    ];

    protected $casts = [
        'views_count' => 'integer',
        'year' => 'integer',
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

    public function media()
    {
        return $this->hasMany(AdMedia::class, 'ad_id');
    }
}
