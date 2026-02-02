<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'status',
        'specs_group_id',
    ];

    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ads for the category.
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    /**
     * Get the specifications associated with this category.
     */
    public function specifications(): BelongsToMany
    {
        return $this->belongsToMany(Specification::class, 'category_specification')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('category_specification.order');
    }

    /**
     * Scope a query to search categories by name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name_en', 'like', "%{$search}%")
              ->orWhere('name_ar', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}