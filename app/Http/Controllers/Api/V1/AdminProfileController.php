<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\UserResource;
use App\Http\Traits\LogsAudit;
use App\Models\AuditLog;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminProfileController extends BaseApiController
{
    use LogsAudit;

    /**
     * Get current admin profile
     * GET /api/v1/admin/profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles');

        return $this->success(
            new UserResource($user),
            'Profile retrieved successfully'
        );
    }

    /**
     * Update admin profile
     * PUT /api/v1/admin/profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
        ]);

        if ($validator->fails()) {
            return $this->error(422, 'Validation failed', $validator->errors()->toArray());
        }

        $oldData = $user->only(['name', 'email', 'phone']);
        
        $user->update($request->only(['name', 'email', 'phone']));

        // Audit log
        $this->auditLog(
            actionType: 'profile.updated',
            resourceType: 'user',
            resourceId: $user->id,
            details: [
                'old' => $oldData,
                'new' => $user->only(['name', 'email', 'phone']),
            ],
            severity: 'info'
        );

        $user->load('roles');

        return $this->success(
            new UserResource($user),
            'Profile updated successfully'
        );
    }

    /**
     * Update profile image
     * POST /api/v1/admin/profile/image
     */
    public function updateImage(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error(422, 'Validation failed', $validator->errors()->toArray());
        }

        $file = $request->file('image');
        $path = $file->store('profiles', 'public');

        // Create media record
        $media = Media::create([
            'user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'public',
        ]);

        // Update user profile image
        $oldImageId = $user->profile_image_id;
        $user->update(['profile_image_id' => $media->id]);

        // Audit log
        $this->auditLog(
            actionType: 'profile.image_updated',
            resourceType: 'user',
            resourceId: $user->id,
            details: [
                'old_image_id' => $oldImageId,
                'new_image_id' => $media->id,
            ],
            severity: 'info'
        );

        return $this->success([
            'image_url' => asset('storage/' . $path),
            'media_id' => $media->id,
        ], 'Profile image updated successfully');
    }

    /**
     * Change password
     * PUT /api/v1/admin/profile/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error(422, 'Validation failed', $validator->errors()->toArray());
        }

        // Verify current password
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return $this->error(400, 'Current password is incorrect', [
                'current_password' => ['The current password is incorrect'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        // Audit log
        $this->auditLog(
            actionType: 'profile.password_changed',
            resourceType: 'user',
            resourceId: $user->id,
            details: ['changed_at' => now()->toIso8601String()],
            severity: 'warning'
        );

        return $this->success(null, 'Password changed successfully');
    }

    /**
     * Get profile activity (recent actions)
     * GET /api/v1/admin/profile/activity
     */
    public function activity(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->get('limit', 20);

        $activities = AuditLog::where('actor_id', $user->id)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action_type,
                    'resource_type' => $log->resource_type,
                    'resource_id' => $log->resource_id,
                    'description' => $this->formatActivityDescription($log),
                    'timestamp' => $log->timestamp->toIso8601String(),
                    'ip_address' => $log->ip_address,
                ];
            });

        return $this->success([
            'activities' => $activities,
            'total' => $activities->count(),
        ], 'Activity retrieved successfully');
    }

    /**
     * Format activity description for display
     */
    private function formatActivityDescription($log): string
    {
        $action = str_replace('.', ' ', $log->action_type);
        $resource = $log->resource_type ?? 'resource';
        
        return ucfirst($action) . " {$resource}" . ($log->resource_id ? " #{$log->resource_id}" : "");
    }
}
