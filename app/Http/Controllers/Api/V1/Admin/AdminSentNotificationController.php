<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\AdminSentNotification;
use App\Http\Resources\NotificationResource;

class AdminSentNotificationController extends BaseApiController
{
    /**
     * List sent admin notifications (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'Unauthorized');
        }

        $query = AdminSentNotification::query();

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->get('title') . '%');
        }

        if ($request->filled('sent_by')) {
            $query->where('sent_by', $request->get('sent_by'));
        }

        $perPage = min($request->get('limit', 20), 100);
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->successPaginated($paginator, 'Sent notifications retrieved');
    }

    /**
     * Show a sent notification log
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'Unauthorized');
        }

        $log = AdminSentNotification::find($id);
        if (!$log) {
            return $this->error(404, 'Sent notification not found');
        }

        return $this->success($log, 'Sent notification retrieved');
    }
}
