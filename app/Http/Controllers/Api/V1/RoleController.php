<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Role\AssignRoleRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseApiController
{
    /**
     * Display a listing of roles.
     *
     * GET /api/v1/roles
     */
    public function index(Request $request): JsonResponse
    {
        $roles = Role::withCount('users')
            ->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return $this->successPaginated(
            $roles->through(fn($role) => new RoleResource($role)),
            'Roles retrieved successfully'
        );
    }

    /**
     * Store a newly created role.
     *
     * POST /api/v1/roles
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $role = Role::create($validated);

        return $this->success(
            new RoleResource($role),
            'Role created successfully',
            201
        );
    }

    /**
     * Display the specified role.
     *
     * GET /api/v1/roles/{role}
     */
    public function show(Role $role): JsonResponse
    {
        $role->loadCount('users');

        return $this->success(
            new RoleResource($role),
            'Role retrieved successfully'
        );
    }

    /**
     * Update the specified role.
     *
     * PUT /api/v1/roles/{role}
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $validated = $request->validated();
        
        $role->update($validated);

        return $this->success(
            new RoleResource($role->fresh()),
            'Role updated successfully'
        );
    }

    /**
     * Remove the specified role.
     *
     * DELETE /api/v1/roles/{role}
     */
    public function destroy(Role $role): JsonResponse
    {
        // Prevent deletion of critical system roles
        if (in_array($role->name, ['admin', 'super_admin'])) {
            return $this->error(
                403,
                'Cannot delete system role',
                ['role' => ['This role cannot be deleted']]
            );
        }

        // Check if role has users assigned
        if ($role->users()->count() > 0) {
            return $this->error(
                409,
                'Cannot delete role with assigned users',
                ['role' => ['Role has users assigned and cannot be deleted']]
            );
        }

        $role->delete();

        return $this->success(null, 'Role deleted successfully', 200);
    }

    /**
     * Assign roles to a user.
     *
     * POST /api/v1/users/{user}/roles
     */
    public function assignRoles(AssignRoleRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();
        
        // Get role IDs from role names
        $roles = Role::whereIn('name', $validated['roles'])->get();


        // Sync roles (replace all existing roles with new ones)
        $user->roles()->sync($roles->pluck('id'));

        // Map roles to account_type and update users table accordingly
        // Priority mapping ensures most-significant role determines account_type
        $roleNames = $roles->pluck('name')->map(fn($n) => strtolower($n))->all();

        $roleToAccount = [
            'super_admin' => 'admin',
            'admin' => 'admin',
            'seller' => 'seller',
            'showroom' => 'showroom',
            'marketer' => 'marketing',
            'user' => 'individual',
            'individual' => 'individual',
        ];

        $priority = ['super_admin', 'admin', 'company', 'dealer', 'marketer', 'user', 'individual'];
        $accountType = null;
        foreach ($priority as $r) {
            if (in_array($r, $roleNames, true)) {
                $accountType = $roleToAccount[$r] ?? null;
                break;
            }
        }

        if ($accountType !== null) {
            $old = $user->account_type;
            $user->account_type = $accountType;
            $user->save();

            // Record audit log for account_type change
            if ($old !== $accountType) {
                try {
                    \App\Models\AccountTypeChange::create([
                        'user_id' => $user->id,
                        'old_account_type' => $old,
                        'new_account_type' => $accountType,
                        'changed_by' => $request->user()?->id,
                    ]);
                } catch (\Throwable $e) {
                    // Do not fail the request if audit logging fails; just log the exception
                    logger()->error('Failed to record account_type change: ' . $e->getMessage());
                }
            }
        }

        // Load fresh user with roles for response
        $user->load('roles');

        return $this->success(
            [
                'user_id' => $user->id,
                'account_type' => $user->account_type,
                'roles' => RoleResource::collection($user->roles)
            ],
            'Roles assigned successfully'
        );
    }

    /**
     * Get roles assigned to a user.
     *
     * GET /api/v1/users/{user}/roles
     */
    public function getUserRoles(User $user): JsonResponse
    {
        $user->load('roles');

        return $this->success(
            [
                'user_id' => $user->id,
                'roles' => RoleResource::collection($user->roles)
            ],
            'User roles retrieved successfully'
        );
    }
}