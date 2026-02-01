<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportResolvedNotification;

class ReportStatusService
{
    /**
     * Valid status transitions
     */
    protected array $validTransitions = [
        Report::STATUS_OPEN => [Report::STATUS_UNDER_REVIEW, Report::STATUS_CLOSED],
        Report::STATUS_UNDER_REVIEW => [Report::STATUS_RESOLVED, Report::STATUS_CLOSED, Report::STATUS_OPEN],
        Report::STATUS_RESOLVED => [Report::STATUS_CLOSED, Report::STATUS_UNDER_REVIEW],
        Report::STATUS_CLOSED => [], // No transitions from closed
    ];

    /**
     * Check if a status transition is valid
     */
    public function canTransition(Report $report, string $newStatus): bool
    {
        $currentStatus = $report->status;

        if (!isset($this->validTransitions[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, $this->validTransitions[$currentStatus]);
    }

    /**
     * Transition a report to a new status
     */
    public function transition(Report $report, string $newStatus, ?string $message = null): bool
    {
        // Allow forced transitions for admins (bypass validation)
        // Or validate the transition
        if (!$this->canTransition($report, $newStatus) && !auth()->user()->isAdmin()) {
            return false;
        }

        $oldStatus = $report->status;
        $report->status = $newStatus;
        $report->save();

        // Send notification to reporter if status is resolved or closed
        if (in_array($newStatus, [Report::STATUS_RESOLVED, Report::STATUS_CLOSED])) {
            $report->reporter->notify(new ReportResolvedNotification($report, $message));
        }

        // Log the transition (could be extended to store in a history table)
        \Log::info("Report {$report->id} status changed from {$oldStatus} to {$newStatus} by user " . auth()->id(), [
            'report_id' => $report->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'message' => $message,
            'changed_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Assign a report to a moderator
     */
    public function assignToModerator(Report $report, int $moderatorId): bool
    {
        $moderator = User::find($moderatorId);

        if (!$moderator || !$moderator->hasAnyRole(['moderator', 'admin', 'super-admin'])) {
            return false;
        }

        $report->assigned_to = $moderatorId;

        // Auto-transition to under_review if currently open
        if ($report->status === Report::STATUS_OPEN) {
            $report->status = Report::STATUS_UNDER_REVIEW;
        }

        $report->save();

        \Log::info("Report {$report->id} assigned to moderator {$moderatorId} by user " . auth()->id(), [
            'report_id' => $report->id,
            'moderator_id' => $moderatorId,
            'assigned_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Get all reports assigned to a specific moderator
     */
    public function getAssignedReports(int $moderatorId, ?string $status = null)
    {
        $query = Report::where('assigned_to', $moderatorId)
            ->with(['reporter', 'target']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get report statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Report::count(),
            'open' => Report::where('status', Report::STATUS_OPEN)->count(),
            'under_review' => Report::where('status', Report::STATUS_UNDER_REVIEW)->count(),
            'resolved' => Report::where('status', Report::STATUS_RESOLVED)->count(),
            'closed' => Report::where('status', Report::STATUS_CLOSED)->count(),
            'unassigned' => Report::whereNull('assigned_to')->count(),
            'pending' => Report::whereIn('status', [Report::STATUS_OPEN, Report::STATUS_UNDER_REVIEW])->count(),
        ];
    }
}
