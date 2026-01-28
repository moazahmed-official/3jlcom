<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NormalAd extends Model
{
    use HasFactory;
    protected $table = 'normal_ads';

    protected $fillable = [
        'ad_id', 'price_cash', 'installment_id', 'start_time', 'update_time'
    ];

    protected $casts = [
        'price_cash' => 'decimal:2'
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}
