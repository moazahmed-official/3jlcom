<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slider extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'sliders';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'image_id',
        'category_id',
        'order',
        'value',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'image_id' => 'integer',
        'category_id' => 'integer',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the media/image associated with the slider.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    /**
     * Get the category associated with the slider.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id');
    }

    /**
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->media?->url ?? $this->media?->path;
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter active sliders only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter inactive sliders only.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope to order by the id field.
     */
    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('id', $direction);
    }

    /**
     * Check if slider is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Activate the slider.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the slider.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }
}
