<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdUpgradeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_id',
        'requested_unique_type_id',
        'user_id',
        'status',
        'user_message',
        'admin_message',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the ad being requested for upgrade.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Get the requested unique ad type.
     */
    public function requestedType(): BelongsTo
    {
        return $this->belongsTo(UniqueAdTypeDefinition::class, 'requested_unique_type_id');
    }

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to filter pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to filter rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
