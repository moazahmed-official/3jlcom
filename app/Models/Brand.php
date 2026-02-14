<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'image',
        'image_id',
    ];

    public function imageMedia()
    {
        return $this->belongsTo(\App\Models\Media::class, 'image_id');
    }

    /**
     * Get the models for this brand.
     */
    public function models(): HasMany
    {
        return $this->hasMany(CarModel::class, 'brand_id');
    }

    /**
     * Accessor for `name` expected by API resources.
     * Falls back to name_en then name_ar.
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->attributes['name_en'] ?? $this->attributes['name_ar'] ?? null);
    }
}