<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdSpecification extends Model
{
    protected $fillable = [
        'ad_id',
        'specification_id',
        'value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ad that owns this specification value.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Get the specification definition.
     */
    public function specification(): BelongsTo
    {
        return $this->belongsTo(Specification::class);
    }
}
