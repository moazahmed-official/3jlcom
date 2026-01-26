<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaishhaAd extends Model
{
    protected $table = 'caishha_ads';

    protected $fillable = [
        'ad_id', 'offers_window_period', 'offers_count', 'sellers_visibility_period'
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}
