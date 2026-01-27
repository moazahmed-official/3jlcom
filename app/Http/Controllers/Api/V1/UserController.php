<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
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

        return (new UserResource($user))
            ->additional([
                'status' => 'success',
                'message' => 'User created successfully',
            ])
            ->response()
            ->setStatusCode(201);
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

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => [
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'items' => UserResource::collection($users->items()),
            ],
        ], 200);
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

        return (new UserResource($user))
            ->additional([
                'status' => 'success',
                'message' => 'User retrieved successfully',
            ])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update the specified user in storage.
     *
     * PUT /api/v1/users/{userId}
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(int $userId): JsonResponse
    {
        // Placeholder for future implementation
        return response()->json([
            'status' => 'error',
            'code' => 501,
            'message' => 'Not implemented yet',
            'errors' => (object) [],
        ], 501);
    }

    /**
     * Remove the specified user from storage.
     *
     * DELETE /api/v1/users/{userId}
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $userId): JsonResponse
    {
        // Placeholder for future implementation
        return response()->json([
            'status' => 'error',
            'code' => 501,
            'message' => 'Not implemented yet',
            'errors' => (object) [],
        ], 501);
    }
}
