<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * AuditLog Model
 * 
 * Immutable audit trail for compliance, forensics, and security monitoring.
 * This model enforces write-once semantics - records cannot be updated or deleted
 * at the application level.
 * 
 * @property int $id
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string|null $actor_role
 * @property string $action_type
 * @property string $resource_type
 * @property string|null $resource_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $correlation_id
 * @property array|null $details
 * @property string $severity
 * @property \Carbon\Carbon $timestamp
 * @property \Carbon\Carbon|null $archived_at
 */
class AuditLog extends Model
{
    // Disable default timestamps; we use custom 'timestamp' field
    public $timestamps = false;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     * All fields are fillable for initial creation only.
     */
    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_role',
        'action_type',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'correlation_id',
        'details',
        'severity',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'details' => 'array',
        'timestamp' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        // Hide sensitive data if needed
    ];

    // ========================================
    // IMMUTABILITY ENFORCEMENT
    // ========================================

    /**
     * Prevent updates to audit logs.
     * Audit logs are write-once for integrity.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \Exception('Audit logs are immutable and cannot be updated.');
    }

    /**
     * Prevent deletion of audit logs.
     * Only archival/export processes should remove logs.
     */
    public function delete(): ?bool
    {
        throw new \Exception('Audit logs are immutable and cannot be deleted. Use archival process instead.');
    }

    /**
     * Prevent force deletion.
     */
    public function forceDelete(): ?bool
    {
        throw new \Exception('Audit logs are immutable and cannot be force deleted.');
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the user who performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Filter by actor ID.
     */
    public function scopeByActor(Builder $query, int $actorId): Builder
    {
        return $query->where('actor_id', $actorId);
    }

    /**
     * Scope: Filter by actor role.
     */
    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('actor_role', $role);
    }

    /**
     * Scope: Filter by action type.
     */
    public function scopeByActionType(Builder $query, string $actionType): Builder
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope: Filter by resource type.
     */
    public function scopeByResourceType(Builder $query, string $resourceType): Builder
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope: Filter by resource ID.
     */
    public function scopeByResourceId(Builder $query, string $resourceId): Builder
    {
        return $query->where('resource_id', $resourceId);
    }

    /**
     * Scope: Filter by severity level (at or above).
     */
    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        $severityLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        $minIndex = array_search($severity, $severityLevels);
        
        if ($minIndex === false) {
            return $query;
        }
        
        $allowedSeverities = array_slice($severityLevels, $minIndex);
        return $query->whereIn('severity', $allowedSeverities);
    }

    /**
     * Scope: Filter by correlation ID.
     */
    public function scopeByCorrelationId(Builder $query, string $correlationId): Builder
    {
        return $query->where('correlation_id', $correlationId);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope: Only non-archived logs.
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope: Only archived logs.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get a human-readable description of this audit event.
     */
    public function getDescriptionAttribute(): string
    {
        return sprintf(
            '%s performed %s on %s #%s',
            $this->actor_name ?? 'System',
            $this->action_type,
            $this->resource_type,
            $this->resource_id ?? 'N/A'
        );
    }

    /**
     * Check if this log is critical (error or higher).
     */
    public function isCritical(): bool
    {
        return in_array($this->severity, ['error', 'critical', 'alert', 'emergency']);
    }

    /**
     * Mark this log as archived (for retention/export processes).
     * This is the ONLY allowed modification.
     */
    public function markAsArchived(): void
    {
        // Directly update database to bypass immutability check
        self::where('id', $this->id)->update(['archived_at' => now()]);
        $this->archived_at = now();
    }

    /**
     * Relationship: reads by admin users marking this log as read.
     */
    public function reads()
    {
        return $this->hasMany(AuditLogRead::class, 'audit_log_id');
    }
}
