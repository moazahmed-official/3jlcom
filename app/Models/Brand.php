<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'image',
    ];

    /**
     * Get the models for this brand.
     */
    public function models(): HasMany
    {
        return $this->hasMany(CarModel::class, 'brand_id');
    }
}