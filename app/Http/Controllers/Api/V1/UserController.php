<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\UserVerificationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\SellerVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends BaseApiController
{
    /**
     * Store a newly created user in storage.
     *
     * POST /api/v1/users
     *
     * @param  StoreUserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'country_id' => $validated['country_id'],
            'account_type' => $validated['account_type'] ?? 'individual',
            'password' => Hash::make($validated['password']),
            'is_verified' => false,
        ]);

        return $this->success(
            new UserResource($user),
            'User created successfully',
            201
        );
    }

    /**
     * Display a listing of users.
     *
     * GET /api/v1/users
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        // Placeholder for future implementation
        // This would include pagination and filtering
        $users = User::paginate(20);

        return $this->successPaginated(
            $users->through(fn($user) => new UserResource($user)),
            'Users retrieved successfully'
        );
    }

    /**
     * Display the specified user.
     *
     * GET /api/v1/users/{userId}
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        return $this->success(
            new UserResource($user),
            'User retrieved successfully'
        );
    }

    /**
     * Update the specified user in storage.
     *
     * PUT /api/v1/users/{user}
     *
     * @param  \App\Http\Requests\User\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $this->success(
            new UserResource($user->fresh()),
            'User updated successfully'
        );
    }

    /**
     * Verify a user (seller/showroom)
     *
     * POST /api/v1/users/{userId}/verify
     *
     * @param  UserVerificationRequest  $request
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(UserVerificationRequest $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        // Check if user has seller verification request
        $verificationRequest = SellerVerificationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$verificationRequest) {
            return $this->error(
                404,
                'No pending verification request found for this user'
            );
        }

        try {
            $verificationRequest->update([
                'status' => $request->input('status'),
                'admin_comments' => $request->input('admin_comments'),
                'verified_by' => $request->user()->id,
                'verified_at' => Carbon::now(),
            ]);

            // If approved, mark user as verified
            if ($request->input('status') === 'approved') {
                $user->update([
                    'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
                ]);
            }

            return $this->success([
                'user_id' => $user->id,
                'verification_status' => $verificationRequest->status,
                'admin_comments' => $verificationRequest->admin_comments,
                'verified_at' => $verificationRequest->verified_at,
            ], 'User verification processed successfully');

        } catch (\Exception $e) {
            return $this->error(500, 'Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * DELETE /api/v1/users/{user}
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        $currentUser = request()->user();
        
        // Prevent self-deletion
        if ($currentUser->id === $user->id) {
            return $this->error(
                403,
                'You cannot delete your own account',
                ['user' => ['Self-deletion is not allowed']]
            );
        }

        // Only admin/super_admin can delete users
        if (! $currentUser->roles()->whereIn('name', ['admin', 'super_admin'])->exists()) {
            return $this->error(
                403,
                'You do not have permission to delete users',
                ['user' => ['Insufficient permissions to delete users']]
            );
        }

        // Prevent deletion of super_admin users by regular admins
        $userRoles = $user->roles()->pluck('name');
        $currentUserRoles = $currentUser->roles()->pluck('name');
        
        if ($userRoles->contains('super_admin') && !$currentUserRoles->contains('super_admin')) {
            return $this->error(
                403,
                'Cannot delete super admin user',
                ['user' => ['Only super admins can delete super admin users']]
            );
        }

        // Detach all roles before deletion to avoid foreign key constraints
        $user->roles()->detach();
        
        // Delete the user
        $user->delete();

        return $this->success(
            null,
            'User deleted successfully'
        );
    }
}
