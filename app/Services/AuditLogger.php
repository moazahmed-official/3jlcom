<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * AuditLogger Service
 * 
 * Centralized service for creating audit log entries.
 * Use this to log admin actions, system events, and critical operations.
 * 
 * Usage in controllers:
 *   use App\Services\AuditLogger;
 *   AuditLogger::log(...);
 * 
 * Or as a trait:
 *   use LogsAudit;
 *   $this->auditLog(...);
 */
class AuditLogger
{
    /**
     * Create an audit log entry.
     * 
     * This method bypasses model policies and guards to ensure
     * system-level logging cannot be blocked.
     * 
     * @param string $actionType Action being performed (e.g., 'user.created', 'package.updated')
     * @param string $resourceType Type of resource affected (e.g., 'User', 'Package', 'Ad')
     * @param string|int|null $resourceId ID of the affected resource
     * @param User|null $actor User performing the action (null for system actions)
     * @param array $details Additional structured data (stored as JSON)
     * @param string $severity Severity level (default: 'info')
     * @param Request|null $request HTTP request for context (IP, user agent)
     * @param string|null $correlationId UUID to trace related events
     * @return AuditLog The created audit log entry
     */
    public static function log(
        string $actionType,
        string $resourceType,
        $resourceId = null,
        ?User $actor = null,
        array $details = [],
        string $severity = 'info',
        ?Request $request = null,
        ?string $correlationId = null
    ): AuditLog {
        // Extract request context
        $ipAddress = null;
        $userAgent = null;
        
        if ($request) {
            $ipAddress = self::getClientIp($request);
            $userAgent = $request->userAgent();
        } elseif (request()) {
            // Fallback to global request helper
            $ipAddress = self::getClientIp(request());
            $userAgent = request()->userAgent();
        }

        // Generate correlation ID if not provided
        if (!$correlationId && request()) {
            // Try to get from header (for distributed tracing)
            $correlationId = request()->header('X-Correlation-ID') 
                ?? request()->header('X-Request-ID')
                ?? Str::uuid()->toString();
        } elseif (!$correlationId) {
            $correlationId = Str::uuid()->toString();
        }

        // Get actor details
        $actorId = $actor?->id;
        $actorName = $actor?->name;
        $actorRole = null;
        
        if ($actor) {
            // Get primary role (first admin-level role found, or first role)
            $roles = $actor->roles()->pluck('name')->toArray();
            $actorRole = collect($roles)->first(fn($role) => in_array($role, ['super_admin', 'admin'])) 
                ?? $roles[0] ?? 'user';
        }

        // Create audit log entry
        // SECURITY: We bypass policies here because audit logging must
        // always work, even if API restrictions would normally block it.
        $auditLog = new AuditLog([
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'actor_role' => $actorRole,
            'action_type' => $actionType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId ? (string)$resourceId : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'correlation_id' => $correlationId,
            'details' => $details,
            'severity' => $severity,
            'timestamp' => now(),
        ]);

        // Save directly to bypass any model restrictions
        $auditLog->saveOrFail();

        return $auditLog;
    }

    /**
     * Log a user management action.
     * 
     * @param string $action Specific action (e.g., 'created', 'updated', 'deleted', 'role_assigned')
     * @param int|string $userId User ID affected
     * @param User|null $actor Admin performing the action
     * @param array $details Additional details
     * @param string $severity Severity level
     * @return AuditLog
     */
    public static function logUserAction(
        string $action,
        $userId,
        ?User $actor = null,
        array $details = [],
        string $severity = 'info'
    ): AuditLog {
        return self::log(
            "user.{$action}",
            'User',
            $userId,
            $actor,
            $details,
            $severity
        );
    }

    /**
     * Log a package/billing action.
     * 
     * @param string $action Action (e.g., 'created', 'updated', 'assigned', 'revoked')
     * @param int|string $packageId Package ID affected
     * @param User|null $actor Admin performing the action
     * @param array $details Additional details
     * @param string $severity Severity level
     * @return AuditLog
     */
    public static function logPackageAction(
        string $action,
        $packageId,
        ?User $actor = null,
        array $details = [],
        string $severity = 'info'
    ): AuditLog {
        return self::log(
            "package.{$action}",
            'Package',
            $packageId,
            $actor,
            $details,
            $severity
        );
    }

    /**
     * Log an ad moderation action.
     * 
     * @param string $action Action (e.g., 'published', 'unpublished', 'rejected', 'deleted')
     * @param int|string $adId Ad ID affected
     * @param User|null $actor Admin performing the action
     * @param array $details Additional details
     * @param string $severity Severity level
     * @return AuditLog
     */
    public static function logAdAction(
        string $action,
        $adId,
        ?User $actor = null,
        array $details = [],
        string $severity = 'info'
    ): AuditLog {
        return self::log(
            "ad.{$action}",
            'Ad',
            $adId,
            $actor,
            $details,
            $severity
        );
    }

    /**
     * Log a system configuration change.
     * 
     * @param string $configKey Configuration key changed
     * @param mixed $oldValue Previous value
     * @param mixed $newValue New value
     * @param User|null $actor Admin performing the action
     * @return AuditLog
     */
    public static function logConfigChange(
        string $configKey,
        $oldValue,
        $newValue,
        ?User $actor = null
    ): AuditLog {
        return self::log(
            'system.config_changed',
            'SystemConfig',
            $configKey,
            $actor,
            [
                'config_key' => $configKey,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ],
            'notice'
        );
    }

    /**
     * Log a critical system error.
     * 
     * @param string $errorType Type of error
     * @param string $message Error message
     * @param array $context Additional context
     * @return AuditLog
     */
    public static function logError(
        string $errorType,
        string $message,
        array $context = []
    ): AuditLog {
        return self::log(
            "system.error.{$errorType}",
            'System',
            null,
            null,
            array_merge([
                'error_message' => $message,
            ], $context),
            'error'
        );
    }

    /**
     * Get the real client IP address, handling proxies and load balancers.
     * 
     * SECURITY: Be cautious with X-Forwarded-For header as it can be spoofed.
     * Trust only if you have a trusted proxy/load balancer.
     * 
     * @param Request $request
     * @return string|null
     */
    protected static function getClientIp(Request $request): ?string
    {
        // Check trusted proxy headers first
        if ($request->header('X-Forwarded-For')) {
            // Get first IP (client) from X-Forwarded-For chain
            $ips = explode(',', $request->header('X-Forwarded-For'));
            return trim($ips[0]);
        }

        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }

        // Fallback to direct connection IP
        return $request->ip();
    }
}
