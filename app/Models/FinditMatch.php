<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class FinditMatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'findit_matches';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'findit_request_id',
        'ad_id',
        'match_score',
        'notified_at',
        'dismissed',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'match_score' => 'integer',
        'notified_at' => 'datetime',
        'dismissed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'match_score' => 0,
        'dismissed' => false,
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the FindIt request this match belongs to.
     */
    public function finditRequest(): BelongsTo
    {
        return $this->belongsTo(FinditRequest::class, 'findit_request_id');
    }

    /**
     * Get the ad that matched.
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to unnotified matches only.
     */
    public function scopeUnnotified(Builder $query): Builder
    {
        return $query->whereNull('notified_at');
    }

    /**
     * Scope to notified matches only.
     */
    public function scopeNotified(Builder $query): Builder
    {
        return $query->whereNotNull('notified_at');
    }

    /**
     * Scope to non-dismissed matches only.
     */
    public function scopeNotDismissed(Builder $query): Builder
    {
        return $query->where('dismissed', false);
    }

    /**
     * Scope to dismissed matches only.
     */
    public function scopeDismissed(Builder $query): Builder
    {
        return $query->where('dismissed', true);
    }

    /**
     * Scope to high-score matches (70+).
     */
    public function scopeHighScore(Builder $query, int $minScore = 70): Builder
    {
        return $query->where('match_score', '>=', $minScore);
    }

    /**
     * Order by match score descending.
     */
    public function scopeOrderByScore(Builder $query): Builder
    {
        return $query->orderBy('match_score', 'desc');
    }

    // ==========================================
    // BUSINESS LOGIC
    // ==========================================

    /**
     * Mark this match as notified.
     */
    public function markNotified(): bool
    {
        $this->notified_at = now();
        return $this->save();
    }

    /**
     * Dismiss this match.
     */
    public function dismiss(): bool
    {
        $this->dismissed = true;
        $result = $this->save();

        // Update the request's match counter
        $this->finditRequest->updateCounters();

        return $result;
    }

    /**
     * Check if this match needs notification.
     */
    public function needsNotification(int $minScore = 70): bool
    {
        return !$this->notified_at 
            && !$this->dismissed 
            && $this->match_score >= $minScore;
    }

    /**
     * Check if the linked ad is still valid/published.
     */
    public function isAdStillValid(): bool
    {
        return $this->ad && $this->ad->status === 'published';
    }

    /**
     * Restore a dismissed match.
     */
    public function restore(): bool
    {
        $this->dismissed = false;
        $result = $this->save();

        // Update the request's match counter
        $this->finditRequest->updateCounters();

        return $result;
    }
}
