<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AuditLogRead;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * AdminAuditLogController
 * 
 * Provides admin-only access to audit logs for compliance, forensics,
 * and security monitoring. Supports filtering, pagination, and CSV export.
 * 
 * SECURITY: All methods require admin authentication and authorization.
 */
class AdminAuditLogController extends Controller
{
    /**
     * Constructor
     * 
     * SECURITY: Enforce policy authorization for all methods.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(AuditLog::class, 'audit_log');
    }

    /**
     * List audit logs with filtering and pagination.
     * 
     * GET /api/v1/admin/audit-logs
     * 
     * Query Parameters:
     * - start_date: Filter logs from this date (ISO 8601 format)
     * - end_date: Filter logs until this date (ISO 8601 format)
     * - actor_id: Filter by user ID who performed the action
     * - actor_role: Filter by role (admin, super_admin, etc.)
     * - action_type: Filter by action type (e.g., user.created, package.updated)
     * - resource_type: Filter by resource type (e.g., User, Package, Ad)
     * - resource_id: Filter by specific resource ID
     * - severity: Filter by minimum severity level
     * - correlation_id: Filter by correlation ID (trace related events)
     * - page: Page number (default: 1)
     * - per_page: Results per page (default: 50, max: 500)
     * - sort: Sort field (default: timestamp)
     * - sort_direction: Sort direction (asc/desc, default: desc)
     * - format: Response format (json/csv, default: json)
     * 
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function index(Request $request)
    {
        // SECURITY: Authorization is handled by middleware + policy
        // Only admins can reach this point
        
        // Validate query parameters
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'actor_id' => 'nullable|integer|exists:users,id',
            'actor_role' => 'nullable|string|max:50',
            'action_type' => 'nullable|string|max:100',
            'resource_type' => 'nullable|string|max:100',
            'resource_id' => 'nullable|string|max:100',
            'severity' => 'nullable|in:debug,info,notice,warning,error,critical,alert,emergency',
            'correlation_id' => 'nullable|string|max:36',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:500',
            'sort' => 'nullable|string|in:timestamp,severity,actor_id,action_type,resource_type',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'format' => 'nullable|string|in:json,csv',
        ]);

        // Build query with filters
        $query = AuditLog::query()->with('actor:id,name,email');

        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('timestamp', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('timestamp', '<=', $request->end_date);
        }

        if ($request->filled('actor_id')) {
            $query->byActor($request->actor_id);
        }

        if ($request->filled('actor_role')) {
            $query->byRole($request->actor_role);
        }

        if ($request->filled('action_type')) {
            $query->byActionType($request->action_type);
        }

        if ($request->filled('resource_type')) {
            $query->byResourceType($request->resource_type);
        }

        if ($request->filled('resource_id')) {
            $query->byResourceId($request->resource_id);
        }

        if ($request->filled('severity')) {
            $query->bySeverity($request->severity);
        }

        if ($request->filled('correlation_id')) {
            $query->byCorrelationId($request->correlation_id);
        }

        // Sorting
        $sortField = $request->get('sort', 'timestamp');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Format handling
        $format = $request->get('format', 'json');

        if ($format === 'csv') {
            return $this->exportCsv($query);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 500);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Audit logs retrieved successfully',
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }

    /**
     * Export audit logs as CSV.
     * 
     * SECURITY: Only admins with export permission can use this.
     * Large exports are streamed to avoid memory issues.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function exportCsv($query)
    {
        // SECURITY: Check export permission explicitly
        $this->authorize('export', AuditLog::class);

        $fileName = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'Timestamp',
                'Actor ID',
                'Actor Name',
                'Actor Role',
                'Action Type',
                'Resource Type',
                'Resource ID',
                'IP Address',
                'Severity',
                'Correlation ID',
                'Details',
            ]);

            // Stream data in chunks to avoid memory issues
            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->timestamp->toIso8601String(),
                        $log->actor_id,
                        $log->actor_name,
                        $log->actor_role,
                        $log->action_type,
                        $log->resource_type,
                        $log->resource_id,
                        $log->ip_address,
                        $log->severity,
                        $log->correlation_id,
                        json_encode($log->details), // Serialize JSON for CSV
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Get a single audit log entry.
     * 
     * GET /api/v1/admin/audit-logs/{id}
     * 
     * @param AuditLog $auditLog
     * @return JsonResponse
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        // SECURITY: Authorization handled by middleware + policy
        
        $auditLog->load('actor:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Audit log retrieved successfully',
            'data' => $auditLog,
        ]);
    }

    /**
     * Get audit statistics.
     * 
     * GET /api/v1/admin/audit-logs/stats
     * 
     * Provides summary statistics for audit logs.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        // SECURITY: Check admin permission
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $stats = [
            'total_logs' => AuditLog::count(),
            'logs_today' => AuditLog::whereDate('timestamp', today())->count(),
            'logs_this_week' => AuditLog::whereBetween('timestamp', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'logs_this_month' => AuditLog::whereMonth('timestamp', now()->month)->count(),
            'critical_logs' => AuditLog::whereIn('severity', ['error', 'critical', 'alert', 'emergency'])->count(),
            'by_action_type' => AuditLog::selectRaw('action_type, COUNT(*) as count')
                ->groupBy('action_type')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'action_type'),
            'by_severity' => AuditLog::selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->get()
                ->pluck('count', 'severity'),
            'top_actors' => AuditLog::selectRaw('actor_id, actor_name, COUNT(*) as count')
                ->whereNotNull('actor_id')
                ->groupBy('actor_id', 'actor_name')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'actor_id' => $item->actor_id,
                    'actor_name' => $item->actor_name,
                    'count' => $item->count,
                ]),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Audit statistics retrieved successfully',
            'data' => $stats,
        ]);
    }

    /**
     * Mark one or more audit logs as read for the current admin user.
     *
     * POST /api/v1/admin/audit-logs/mark-read
     * Body: { ids: [1,2,3] }
     */
    public function markRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:audit_logs,id',
        ]);

        $userId = $request->user()->id;
        $count = 0;

        foreach ($validated['ids'] as $id) {
            $record = AuditLogRead::updateOrCreate(
                ['audit_log_id' => $id, 'user_id' => $userId],
                ['read_at' => now()]
            );
            if ($record) $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} log(s) as read",
        ]);
    }

    /**
     * Mark one or more audit logs as unread for the current admin user.
     *
     * POST /api/v1/admin/audit-logs/mark-unread
     * Body: { ids: [1,2,3] }
     */
    public function markUnread(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:audit_logs,id',
        ]);

        $userId = $request->user()->id;

        $deleted = AuditLogRead::whereIn('audit_log_id', $validated['ids'])
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Marked {$deleted} log(s) as unread",
        ]);
    }

    /**
     * Archive one or more audit logs. This uses AuditLog::markAsArchived().
     *
     * POST /api/v1/admin/audit-logs/archive
     * Body: { ids: [1,2,3] }
     */
    public function archive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:audit_logs,id',
        ]);

        $count = 0;
        DB::beginTransaction();
        try {
            foreach ($validated['ids'] as $id) {
                $log = AuditLog::find($id);
                if ($log && is_null($log->archived_at)) {
                    $log->markAsArchived();
                    $count++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive audit logs',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Archived {$count} log(s)",
        ]);
    }

    /**
     * Bulk action endpoint for audit logs.
     * Supports actions: mark_read, mark_unread, archive
     *
     * POST /api/v1/admin/audit-logs/bulk
     * Body: { action: 'mark_read', ids: [1,2,3] }
     */
    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:mark_read,mark_unread,archive',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:audit_logs,id',
        ]);

        switch ($validated['action']) {
            case 'mark_read':
                $request->merge(['ids' => $validated['ids']]);
                return $this->markRead($request);
            case 'mark_unread':
                $request->merge(['ids' => $validated['ids']]);
                return $this->markUnread($request);
            case 'archive':
                $request->merge(['ids' => $validated['ids']]);
                return $this->archive($request);
            default:
                return response()->json(['success' => false, 'message' => 'Unknown action'], 400);
        }
    }

    /**
     * Get unread count for the current admin user (non-archived logs not read by the user).
     *
     * GET /api/v1/admin/audit-logs/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $count = AuditLog::notArchived()
            ->whereDoesntHave('reads', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->count();

        return response()->json([
            'success' => true,
            'data' => ['unread' => $count],
        ]);
    }

    /**
     * Additional aggregated counts for admin UI.
     *
     * GET /api/v1/admin/audit-logs/counts
     */
    public function counts(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $total = AuditLog::count();
        $archived = AuditLog::whereNotNull('archived_at')->count();
        $notArchived = AuditLog::whereNull('archived_at')->count();
        $unread = AuditLog::notArchived()->whereDoesntHave('reads', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();
        $critical = AuditLog::whereIn('severity', ['error', 'critical', 'alert', 'emergency'])->count();

        return response()->json([
            'success' => true,
            'data' => compact('total', 'archived', 'notArchived', 'unread', 'critical'),
        ]);
    }

    /**
     * Delete is intentionally not supported to preserve immutability.
     * Provide a clear response explaining the policy.
     */
    public function destroy(AuditLog $auditLog)
    {
        return response()->json([
            'success' => false,
            'message' => 'Audit logs are immutable and cannot be deleted via API. Use archive instead.',
        ], 405);
    }
}
