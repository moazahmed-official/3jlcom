<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CarModel extends Model
{
    use HasFactory;

    protected $table = 'models';

    // Disable updated_at since the migration only has created_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'brand_id',
        'name_en',
        'name_ar',
        'year_from',
        'year_to',
        'image',
    ];

    protected $casts = [
        'year_from' => 'integer',
        'year_to' => 'integer',
    ];

    /**
     * Get the brand that owns this model.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
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