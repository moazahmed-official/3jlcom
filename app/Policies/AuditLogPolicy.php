<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * AuditLogPolicy
 * 
 * Authorization policy for audit log access.
 * Only admin and super_admin roles can view audit logs.
 * 
 * SECURITY NOTE: Audit logs are highly sensitive and should only be
 * accessible to trusted administrators for compliance and forensics.
 */
class AuditLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any audit logs.
     * 
     * SECURITY: Only admins and super_admins can access audit logs.
     * This is critical for compliance and security.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view a specific audit log.
     * 
     * SECURITY: Same as viewAny - admins only.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can create audit logs.
     * 
     * NOTE: Audit logs are created automatically by the system.
     * Direct creation via API should be blocked, but the AuditLogger
     * service bypasses policy checks.
     */
    public function create(User $user): bool
    {
        // Block manual creation through API
        // System uses AuditLogger service which doesn't check policies
        return false;
    }

    /**
     * Determine whether the user can update audit logs.
     * 
     * IMMUTABILITY: Audit logs are write-once and cannot be updated.
     * This is enforced at the model level as well.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false; // Always deny - immutable by design
    }

    /**
     * Determine whether the user can delete audit logs.
     * 
     * IMMUTABILITY: Audit logs cannot be deleted via API.
     * Only automated archival/retention processes should remove logs.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false; // Always deny - immutable by design
    }

    /**
     * Determine whether the user can restore audit logs.
     * 
     * NOTE: Not applicable since we don't use soft deletes.
     */
    public function restore(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete audit logs.
     * 
     * IMMUTABILITY: Always denied.
     */
    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return false; // Always deny - immutable by design
    }

    /**
     * Determine whether the user can export audit logs.
     * 
     * SECURITY: Only admins can export audit logs (CSV, etc.).
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
