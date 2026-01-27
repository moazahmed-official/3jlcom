<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
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
            return $this->error(
                401,
                'Invalid credentials'
            );
        }

        $tokenResult = $user->createToken('api-token');
        $plainTextToken = $tokenResult->plainTextToken;

        $expiresIn = config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null;

        return $this->success(
            new AuthResource([
                'token' => $plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => $expiresIn,
                'user' => $user,
            ]),
            'Authenticated'
        );
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return $this->success(
            null,
            'Logged out'
        );
    }
}
