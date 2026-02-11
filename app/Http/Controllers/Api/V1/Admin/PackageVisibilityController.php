<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Traits\LogsAudit;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageVisibilityController extends BaseApiController
{
    use LogsAudit;

    /**
     * Update package visibility settings.
     *
     * POST /api/v1/admin/packages/{package}/visibility
     */
    public function update(Request $request, Package $package): JsonResponse
    {
        $request->validate([
            'visibility_type' => 'required|string|in:public,role_based,user_specific',
            'allowed_roles' => 'nullable|array',
            'allowed_roles.*' => 'string|in:user,seller,showroom,marketer,admin',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $oldData = [
            'visibility_type' => $package->visibility_type,
            'allowed_roles' => $package->allowed_roles,
        ];

        DB::transaction(function () use ($request, $package) {
            // Update visibility type
            $package->visibility_type = $request->visibility_type;

            // Handle allowed roles for role_based visibility
            if ($request->visibility_type === Package::VISIBILITY_ROLE_BASED) {
                if (!$request->has('allowed_roles') || empty($request->allowed_roles)) {
                    return $this->error(422, 'allowed_roles is required for role_based visibility', [
                        'allowed_roles' => ['Specify at least one role for role-based visibility']
                    ]);
                }
                $package->allowed_roles = $request->allowed_roles;
            } else {
                $package->allowed_roles = null;
            }

            // Handle user-specific access
            if ($request->visibility_type === Package::VISIBILITY_USER_SPECIFIC) {
                if ($request->has('user_ids') && !empty($request->user_ids)) {
                    $package->userAccess()->sync($request->user_ids);
                } else {
                    // Clear all user access if switching to user_specific without providing users
                    $package->userAccess()->sync([]);
                }
            } else {
                // Clear user access for non-user_specific visibility
                $package->userAccess()->sync([]);
            }

            $package->save();
        });

        $this->logAudit('updated_visibility', Package::class, $package->id, $oldData, [
            'visibility_type' => $package->visibility_type,
            'allowed_roles' => $package->allowed_roles,
            'user_access_count' => $package->userAccess()->count(),
        ]);

        return $this->success(
            [
                'package' => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'visibility_type' => $package->visibility_type,
                    'allowed_roles' => $package->allowed_roles,
                    'user_access_count' => $package->userAccess()->count(),
                ],
            ],
            'Package visibility updated successfully'
        );
    }

    /**
     * Get current visibility settings for a package.
     *
     * GET /api/v1/admin/packages/{package}/visibility
     */
    public function show(Package $package): JsonResponse
    {
        $package->load('userAccess:id,name,email,role');

        return $this->success([
            'visibility_type' => $package->visibility_type,
            'allowed_roles' => $package->allowed_roles,
            'user_access' => $package->userAccess->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ];
            }),
        ], 'Package visibility settings retrieved');
    }

    /**
     * Grant access to specific users (for user_specific visibility).
     *
     * POST /api/v1/admin/packages/{package}/grant-access
     */
    public function grantAccess(Request $request, Package $package): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        if ($package->visibility_type !== Package::VISIBILITY_USER_SPECIFIC) {
            return $this->error(422, 'Package must have user_specific visibility type to grant user access', [
                'visibility_type' => ['Current visibility type is: ' . $package->visibility_type]
            ]);
        }

        $package->grantAccessToUsers($request->user_ids);

        $this->logAudit('granted_access', Package::class, $package->id, null, [
            'user_ids' => $request->user_ids,
        ]);

        return $this->success([
            'granted_users_count' => count($request->user_ids),
            'total_users_with_access' => $package->userAccess()->count(),
        ], 'Access granted successfully');
    }

    /**
     * Revoke access from specific users.
     *
     * POST /api/v1/admin/packages/{package}/revoke-access
     */
    public function revokeAccess(Request $request, Package $package): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        if ($package->visibility_type !== Package::VISIBILITY_USER_SPECIFIC) {
            return $this->error(422, 'Package must have user_specific visibility type', [
                'visibility_type' => ['Current visibility type is: ' . $package->visibility_type]
            ]);
        }

        $package->revokeAccessFromUsers($request->user_ids);

        $this->logAudit('revoked_access', Package::class, $package->id, null, [
            'user_ids' => $request->user_ids,
        ]);

        return $this->success([
            'revoked_users_count' => count($request->user_ids),
            'total_users_with_access' => $package->userAccess()->count(),
        ], 'Access revoked successfully');
    }

    /**
     * List users who have access to a user-specific package.
     *
     * GET /api/v1/admin/packages/{package}/users-with-access
     */
    public function usersWithAccess(Package $package): JsonResponse
    {
        if ($package->visibility_type !== Package::VISIBILITY_USER_SPECIFIC) {
            return $this->error(422, 'Package is not user_specific', [
                'visibility_type' => ['Current visibility type is: ' . $package->visibility_type]
            ]);
        }

        $users = $package->userAccess()
            ->select('id', 'name', 'email', 'role')
            ->get();

        return $this->success([
            'users' => $users,
            'total_count' => $users->count(),
        ], 'Users with access retrieved');
    }
}
