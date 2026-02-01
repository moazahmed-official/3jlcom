<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        // Only admins and moderators can view all reports
        return $user->hasAnyRole(['admin', 'super-admin', 'moderator']);
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        // Owner, assigned moderator, or admin can view
        return $user->id === $report->reported_by_user_id 
            || $user->id === $report->assigned_to
            || $user->hasAnyRole(['admin', 'super-admin', 'moderator']);
    }

    /**
     * Determine whether the user can create reports.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create reports
        return true;
    }

    /**
     * Determine whether the user can assign the report to a moderator.
     */
    public function assign(User $user, Report $report): bool
    {
        // Only admins can assign reports
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can update the report status.
     */
    public function updateStatus(User $user, Report $report): bool
    {
        // Admin, moderator, or assigned moderator can update status
        return $user->id === $report->assigned_to
            || $user->hasAnyRole(['admin', 'super-admin', 'moderator']);
    }

    /**
     * Determine whether the user can delete the report.
     */
    public function delete(User $user, Report $report): bool
    {
        // Only admins can delete reports
        return $user->hasAnyRole(['admin', 'super-admin']);
    }
}
