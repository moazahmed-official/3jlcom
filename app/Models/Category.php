<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    /**
     * Get the ads for the category.
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}