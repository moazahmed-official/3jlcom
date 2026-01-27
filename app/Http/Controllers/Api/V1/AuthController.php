<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // Allow login by phone OR email
        $user = null;
        if ($request->filled('email')) {
            $user = User::where('email', $request->input('email'))->first();
        } elseif ($request->filled('phone')) {
            $user = User::where('phone', $request->input('phone'))->first();
        }

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Invalid credentials',
                'errors' => (object) [],
            ], 401);
        }

        $tokenResult = $user->createToken('api-token');
        $plainTextToken = $tokenResult->plainTextToken;

        $expiresIn = config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null;

        return (new AuthResource([
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'user' => $user,
        ]))->additional([
            'status' => 'success',
            'message' => 'Authenticated',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out',
        ], 200);
    }
}
