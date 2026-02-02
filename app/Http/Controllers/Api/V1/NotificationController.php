<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use App\Notifications\AdminNotification;

class NotificationController extends BaseApiController
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = $user->notifications();

        // Filter by read status
        if ($request->has('read')) {
            if ($request->boolean('read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        // Filter by type
        if ($request->filled('type')) {
            $typeMap = [
                'review_received' => 'App\\Notifications\\ReviewReceivedNotification',
                'report_resolved' => 'App\\Notifications\\ReportResolvedNotification',
                'findit_match' => 'App\\Notifications\\FindItNewMatchNotification',
                'findit_offer' => 'App\\Notifications\\FindItNewOfferNotification',
                'findit_offer_accepted' => 'App\\Notifications\\FindItOfferAcceptedNotification',
                'findit_offer_rejected' => 'App\\Notifications\\FindItOfferRejectedNotification',
                'seller_verification' => 'App\\Notifications\\AdminSellerVerificationRequestNotification',
                'admin_message' => 'App\\Notifications\\AdminNotification',
            ];

            if (isset($typeMap[$request->type])) {
                $query->where('type', $typeMap[$request->type]);
            }
        }

        $query->orderBy('created_at', 'desc');

        $limit = min($request->get('limit', 20), 100);
        $notifications = $query->paginate($limit);

        // Get unread count
        $unreadCount = $user->unreadNotifications()->count();

        $response = $this->successPaginated(
            $notifications->setCollection(
                $notifications->getCollection()->map(fn($n) => new NotificationResource($n))
            ),
            'Notifications retrieved successfully'
        );

        // Add unread count to response
        $data = $response->getData(true);
        $data['data']['unread_count'] = $unreadCount;

        return response()->json($data);
    }

    /**
     * Display the specified notification.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->find($id);

        if (!$notification) {
            return $this->error(404, 'Notification not found');
        }

        return $this->success(
            new NotificationResource($notification),
            'Notification retrieved successfully'
        );
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->find($id);

        if (!$notification) {
            return $this->error(404, 'Notification not found');
        }

        $notification->markAsRead();

        return $this->success(
            new NotificationResource($notification->fresh()),
            'Notification marked as read'
        );
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();
        
        $request->user()->unreadNotifications->markAsRead();

        return $this->success(
            ['marked_count' => $count],
            "{$count} notification(s) marked as read"
        );
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->find($id);

        if (!$notification) {
            return $this->error(404, 'Notification not found');
        }

        $notification->delete();

        return $this->success([], 'Notification deleted successfully');
    }

    /**
     * Send a notification to user(s) - Admin only.
     */
    public function send(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return $this->error(403, 'You are not authorized to send notifications');
        }

        $validated = $request->validate([
            'target' => ['nullable', 'string', 'in:user,group,all'],
            'target_id' => ['required_if:target,user', 'nullable', 'integer'],
            'target_role' => ['required_if:target,group', 'nullable', 'string', 'exists:roles,name'],
            'user_ids' => ['nullable', 'array', 'required_without:target'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'data' => ['nullable', 'array'],
            'action_url' => ['nullable', 'string', 'url'],
            'channel' => ['nullable', 'string', 'in:database,mail,fcm'], // Optional, not used in current implementation
        ]);

        $notificationData = [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'data' => $validated['data'] ?? [],
            'action_url' => $validated['action_url'] ?? null,
            'sent_by' => $request->user()->id,
        ];

        $targetUsers = collect();

        // Check if user_ids is provided (new approach)
        if (!empty($validated['user_ids'])) {
            $targetUsers = \App\Models\User::whereIn('id', $validated['user_ids'])->get();
            
            if ($targetUsers->isEmpty()) {
                return $this->error(404, 'No users found with the specified IDs');
            }
            
            // Send to collected users
            foreach ($targetUsers as $user) {
                $user->notify(new AdminNotification($notificationData));
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => ['recipients_count' => $targetUsers->count()],
            ], 202);
        }

        // Legacy approach using target
        switch ($validated['target']) {
            case 'user':
                $user = \App\Models\User::find($validated['target_id']);
                if (!$user) {
                    return $this->error(404, 'Target user not found');
                }
                $targetUsers->push($user);
                break;

            case 'group':
                $targetUsers = \App\Models\User::whereHas('roles', function ($q) use ($validated) {
                    $q->where('name', $validated['target_role']);
                })->get();
                
                if ($targetUsers->isEmpty()) {
                    return $this->error(404, 'No users found with the specified role');
                }
                break;

            case 'all':
                // Use chunking for large user bases
                $count = \App\Models\User::count();
                \App\Models\User::chunk(100, function ($users) use ($notificationData) {
                    foreach ($users as $user) {
                        $user->notify(new AdminNotification($notificationData));
                    }
                });
                
                return response()->json([
                    'success' => true,
                    'message' => "Notification queued for {$count} user(s)",
                    'data' => ['recipients_count' => $count],
                ], 202);
        }

        // Send to collected users
        foreach ($targetUsers as $user) {
            $user->notify(new AdminNotification($notificationData));
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully',
            'data' => ['recipients_count' => $targetUsers->count()],
        ], 202);
    }
}
