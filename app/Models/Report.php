<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'reason',
        'reported_by_user_id',
        'target_type',
        'target_id',
        'status',
        'assigned_to',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    // Valid status transitions
    public const STATUS_OPEN = 'open';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const VALID_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    // Valid target types
    public const TARGET_TYPE_AD = 'ad';
    public const TARGET_TYPE_USER = 'user';
    public const TARGET_TYPE_DEALER = 'dealer';

    public const VALID_TARGET_TYPES = [
        self::TARGET_TYPE_AD,
        self::TARGET_TYPE_USER,
        self::TARGET_TYPE_DEALER,
    ];

    /**
     * Get the user who created the report
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
     * Get the moderator assigned to this report
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the target entity being reported (polymorphic)
     */
    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get open reports
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get reports under review
     */
    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    /**
     * Scope to get resolved reports
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope to get closed reports
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope to get pending reports (open or under review)
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Scope to get reports by a specific user
     */
    public function scopeByReporter(Builder $query, int $userId): Builder
    {
        return $query->where('reported_by_user_id', $userId);
    }

    /**
     * Scope to get reports assigned to a specific moderator
     */
    public function scopeAssignedTo(Builder $query, int $moderatorId): Builder
    {
        return $query->where('assigned_to', $moderatorId);
    }

    /**
     * Scope to get unassigned reports
     */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope to filter by target type
     */
    public function scopeTargetType(Builder $query, string $targetType): Builder
    {
        return $query->where('target_type', $targetType);
    }

    /**
     * Scope to get recent reports
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if the report is open
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if the report is under review
     */
    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    /**
     * Check if the report is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if the report is closed
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Check if the report is pending (open or under review)
     */
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Check if the report is assigned to a moderator
     */
    public function isAssigned(): bool
    {
        return !is_null($this->assigned_to);
    }

    /**
     * Transition the report to a new status
     */
    public function transitionTo(string $newStatus): bool
    {
        if (!in_array($newStatus, self::VALID_STATUSES)) {
            return false;
        }

        $this->status = $newStatus;
        return $this->save();
    }

    /**
     * Assign the report to a moderator
     */
    public function assignToModerator(int $moderatorId): bool
    {
        $this->assigned_to = $moderatorId;
        
        // Auto-transition to under_review if currently open
        if ($this->isOpen()) {
            $this->status = self::STATUS_UNDER_REVIEW;
        }
        
        return $this->save();
    }

    /**
     * Mark the report as resolved
     */
    public function markAsResolved(): bool
    {
        return $this->transitionTo(self::STATUS_RESOLVED);
    }

    /**
     * Mark the report as closed
     */
    public function markAsClosed(): bool
    {
        return $this->transitionTo(self::STATUS_CLOSED);
    }
}
