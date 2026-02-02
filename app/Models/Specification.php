<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specification extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'type',
        'values',
        'image_id',
    ];

    protected $casts = [
        'values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the image associated with the specification.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    /**
     * Get the categories that use this specification.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_specification')
            ->withPivot('order')
            ->withTimestamps();
    }

    /**
     * Scope a query to search specifications by name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name_en', 'like', "%{$search}%")
              ->orWhere('name_ar', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
