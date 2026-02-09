<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\AssignReportRequest;
use App\Http\Requests\Report\UpdateReportStatusRequest;
use App\Http\Resources\ReportResource;
use App\Http\Traits\LogsAudit;
use App\Models\Report;
use App\Services\ReportStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends BaseApiController
{
    use LogsAudit;

    protected ReportStatusService $statusService;

    public function __construct(ReportStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Store a newly created report.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $report = Report::create([
            'reported_by_user_id' => auth()->id(),
            'target_type' => $validated['target_type'],
            'target_id' => $validated['target_id'],
            'reason' => $validated['reason'],
            'title' => $validated['title'] ?? null,
            'status' => Report::STATUS_OPEN,
        ]);

        $report->load(['reporter', 'target']);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully. Our team will review it shortly.',
            'data' => new ReportResource($report),
        ], 201);
    }

    /**
     * Get the authenticated user's reports.
     */
    public function myReports(Request $request): JsonResponse
    {
        $query = Report::where('reported_by_user_id', auth()->id())
            ->with(['target']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('created_at', 'desc');

        $limit = min($request->get('limit', 15), 50);
        $reports = $query->paginate($limit);

        return $this->successPaginated($reports->setCollection(
            $reports->getCollection()->map(fn($report) => new ReportResource($report))
        ), 'Your reports retrieved successfully');
    }

    /**
     * Admin: Display a listing of all reports.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $query = Report::with(['reporter', 'target', 'assignedTo']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by target type
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        // Filter by assigned moderator
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $query->orderBy('created_at', 'desc');

        $limit = min($request->get('limit', 15), 50);
        $reports = $query->paginate($limit);

        return $this->successPaginated($reports->setCollection(
            $reports->getCollection()->map(fn($report) => new ReportResource($report))
        ), 'All reports retrieved successfully');
    }

    /**
     * Display the specified report.
     */
    public function show(Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        $report->load(['reporter', 'target', 'assignedTo']);

        return $this->success(
            new ReportResource($report),
            'Report retrieved successfully'
        );
    }

    /**
     * Admin: Assign report to a moderator.
     */
    public function assign(AssignReportRequest $request, Report $report): JsonResponse
    {
        $validated = $request->validated();

        $oldAssignee = $report->assigned_to;
        $success = $this->statusService->assignToModerator($report, $validated['moderator_id']);

        if (!$success) {
            return $this->error(400, 'Failed to assign report to moderator');
        }

        $this->auditLog(
            actionType: 'report.assigned',
            resourceType: 'report',
            resourceId: $report->id,
            details: [
                'report_id' => $report->id,
                'target_type' => $report->target_type,
                'target_id' => $report->target_id,
                'old_assignee' => $oldAssignee,
                'new_assignee' => $validated['moderator_id']
            ],
            severity: 'info'
        );

        $report->load(['reporter', 'target', 'assignedTo']);

        return $this->success(
            new ReportResource($report),
            'Report assigned successfully'
        );
    }

    /**
     * Update the report status.
     */
    public function updateStatus(UpdateReportStatusRequest $request, Report $report): JsonResponse
    {
        $validated = $request->validated();

        $oldStatus = $report->status;
        $newStatus = $validated['status'];
        $message = $validated['message'] ?? null;
        $success = $this->statusService->transition($report, $newStatus, $message);

        if (!$success) {
            return $this->error(400, 'Invalid status transition');
        }

        $this->auditLog(
            actionType: 'report.status_updated',
            resourceType: 'report',
            resourceId: $report->id,
            details: [
                'report_id' => $report->id,
                'target_type' => $report->target_type,
                'target_id' => $report->target_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => $message
            ],
            severity: 'info'
        );

        $report->load(['reporter', 'target', 'assignedTo']);

        return $this->success(
            new ReportResource($report),
            'Report status updated successfully'
        );
    }

    /**
     * Mark report as resolved.
     */
    public function resolve(Request $request, Report $report): JsonResponse
    {
        $this->authorize('updateStatus', $report);

        $message = $request->input('message');
        $success = $this->statusService->transition($report, Report::STATUS_RESOLVED, $message);

        if (!$success) {
            return $this->error(400, 'Failed to resolve report');
        }

        $report->load(['reporter', 'target', 'assignedTo']);

        return $this->success(
            new ReportResource($report),
            'Report marked as resolved'
        );
    }

    /**
     * Mark report as closed.
     */
    public function close(Request $request, Report $report): JsonResponse
    {
        $this->authorize('updateStatus', $report);

        $message = $request->input('message');
        $success = $this->statusService->transition($report, Report::STATUS_CLOSED, $message);

        if (!$success) {
            return $this->error(400, 'Failed to close report');
        }

        $report->load(['reporter', 'target', 'assignedTo']);

        return $this->success(
            new ReportResource($report),
            'Report closed successfully'
        );
    }

    /**
     * Admin: Delete a report.
     */
    public function destroy(Report $report): JsonResponse
    {
        $this->authorize('delete', $report);

        $this->auditLogDestructive(
            actionType: 'report.deleted',
            resourceType: 'report',
            resourceId: $report->id,
            details: [
                'target_type' => $report->target_type,
                'target_id' => $report->target_id,
                'status' => $report->status
            ]
        );

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ], 200);
    }
}
