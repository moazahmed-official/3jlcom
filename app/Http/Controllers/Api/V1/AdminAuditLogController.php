<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
}
