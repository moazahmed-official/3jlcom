<?php

namespace App\Http\Traits;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * LogsAudit Trait
 * 
 * Convenient trait for controllers that need to log audit events.
 * Provides shorthand methods for common audit logging scenarios.
 * 
 * Usage:
 *   class MyController extends Controller {
 *       use LogsAudit;
 *       
 *       public function update(Request $request, User $user) {
 *           // ... update logic ...
 *           $this->auditLog('user.updated', 'User', $user->id, [
 *               'changes' => $request->only(['name', 'email'])
 *           ]);
 *       }
 *   }
 */
trait LogsAudit
{
    /**
     * Log an audit event.
     * 
     * @param string $actionType Action being performed
     * @param string $resourceType Type of resource
     * @param string|int|null $resourceId Resource ID
     * @param array $details Additional details
     * @param string $severity Severity level
     * @return AuditLog
     */
    protected function auditLog(
        string $actionType,
        string $resourceType,
        $resourceId = null,
        array $details = [],
        string $severity = 'info'
    ): AuditLog {
        return AuditLogger::log(
            $actionType,
            $resourceType,
            $resourceId,
            request()->user(),
            $details,
            $severity,
            request()
        );
    }

    /**
     * Log a user management action.
     * 
     * @param string $action Action name (created, updated, deleted, etc.)
     * @param int|string $userId User ID
     * @param array $details Additional details
     * @param string $severity Severity level
     * @return AuditLog
     */
    protected function auditLogUser(
        string $action,
        $userId,
        array $details = [],
        string $severity = 'info'
    ): AuditLog {
        return AuditLogger::logUserAction(
            $action,
            $userId,
            request()->user(),
            $details,
            $severity
        );
    }

    /**
     * Log a package/billing action.
     * 
     * @param string $action Action name
     * @param int|string $packageId Package ID
     * @param array $details Additional details
     * @param string $severity Severity level
     * @return AuditLog
     */
    protected function auditLogPackage(
        string $action,
        $packageId,
        array $details = [],
        string $severity = 'info'
    ): AuditLog {
        return AuditLogger::logPackageAction(
            $action,
            $packageId,
            request()->user(),
            $details,
            $severity
        );
    }

    /**
     * Log an ad moderation action.
     * 
     * @param string $action Action name
     * @param int|string $adId Ad ID
     * @param array $details Additional details
     * @param string $severity Severity level
     * @return AuditLog
     */
    protected function auditLogAd(
        string $action,
        $adId,
        array $details = [],
        string $severity = 'info'
    ): AuditLog {
        return AuditLogger::logAdAction(
            $action,
            $adId,
            request()->user(),
            $details,
            $severity
        );
    }

    /**
     * Log a destructive action with warning severity.
     * 
     * @param string $actionType Action type
     * @param string $resourceType Resource type
     * @param string|int|null $resourceId Resource ID
     * @param array $details Additional details
     * @return AuditLog
     */
    protected function auditLogDestructive(
        string $actionType,
        string $resourceType,
        $resourceId = null,
        array $details = []
    ): AuditLog {
        return $this->auditLog(
            $actionType,
            $resourceType,
            $resourceId,
            $details,
            'warning'
        );
    }

    /**
     * Log a critical security event.
     * 
     * @param string $actionType Action type
     * @param string $resourceType Resource type
     * @param string|int|null $resourceId Resource ID
     * @param array $details Additional details
     * @return AuditLog
     */
    protected function auditLogSecurity(
        string $actionType,
        string $resourceType,
        $resourceId = null,
        array $details = []
    ): AuditLog {
        return $this->auditLog(
            $actionType,
            $resourceType,
            $resourceId,
            $details,
            'alert'
        );
    }
}
