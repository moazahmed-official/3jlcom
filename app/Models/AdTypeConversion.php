<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdTypeConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_id',
        'user_id',
        'from_type',
        'to_type',
        'unique_ad_type_id',
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uniqueAdTypeDefinition(): BelongsTo
    {
        return $this->belongsTo(UniqueAdTypeDefinition::class, 'unique_ad_type_id');
    }
}
