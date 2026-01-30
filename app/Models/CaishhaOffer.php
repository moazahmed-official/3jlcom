<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaishhaOffer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'caishha_offers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ad_id',
        'user_id',
        'price',
        'comment',
        'status',
        'is_visible_to_seller',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_visible_to_seller' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ad that this offer belongs to.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    /**
     * Get the Caishha ad details that this offer belongs to.
     */
    public function caishhaAd(): BelongsTo
    {
        return $this->belongsTo(CaishhaAd::class, 'ad_id', 'ad_id');
    }

    /**
     * Get the user who submitted this offer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to filter pending offers only.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter accepted offers only.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to filter rejected offers only.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to filter offers visible to seller.
     */
    public function scopeVisibleToSeller($query)
    {
        return $query->where('is_visible_to_seller', true);
    }

    /**
     * Check if the offer is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the offer is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if the offer is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Accept this offer.
     */
    public function accept(): bool
    {
        return $this->update([
            'status' => 'accepted',
            'is_visible_to_seller' => true,
        ]);
    }

    /**
     * Reject this offer.
     */
    public function reject(): bool
    {
        return $this->update([
            'status' => 'rejected',
            'is_visible_to_seller' => true,
        ]);
    }
}
