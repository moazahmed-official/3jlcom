<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function register(RegisterRequest $request)
    {
        try {
            DB::beginTransaction();

            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpiresAt = Carbon::now()->addMinutes(10);

            // Create user
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'country_id' => $request->input('country_id'),
                'password' => Hash::make($request->input('password')),
                'account_type' => $request->input('account_type'),
                'otp' => Hash::make($otp),
                'otp_expires_at' => $otpExpiresAt,
                'email_verified_at' => null,
            ]);

            // Send OTP notification
            $user->notify(new SendOtpNotification($otp, 'account verification'));

            DB::commit();

            return $this->success([
                'user_id' => $user->id,
                'phone' => $user->phone,
                'expires_in_minutes' => 10,
            ], 'Registration successful. Please verify your account with the OTP sent to your phone.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Registration failed: ' . $e->getMessage());
        }
    }

    public function verify(VerifyOtpRequest $request)
    {
        try {
            $user = User::where('phone', $request->input('phone'))->first();

            if (!$user) {
                return $this->error(404, 'User not found.');
            }

            if (!$user->otp || !$user->otp_expires_at) {
                return $this->error(400, 'No OTP found for this user.');
            }

            if (Carbon::now()->gt($user->otp_expires_at)) {
                return $this->error(400, 'OTP has expired. Please request a new one.');
            }

            if (!Hash::check($request->input('code'), $user->otp)) {
                return $this->error(400, 'Invalid OTP code.');
            }

            // Verify user and clear OTP
            $user->update([
                'email_verified_at' => Carbon::now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            // Create token
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
                'Account verified successfully.'
            );

        } catch (\Exception $e) {
            return $this->error(500, 'Verification failed: ' . $e->getMessage());
        }
    }

    public function passwordResetRequest(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
        ]);

        try {
            $user = User::where('phone', $request->input('phone'))->first();

            if (!$user) {
                return $this->error(404, 'User not found.');
            }

            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpiresAt = Carbon::now()->addMinutes(10);

            // Update user with OTP
            $user->update([
                'otp' => Hash::make($otp),
                'otp_expires_at' => $otpExpiresAt,
            ]);

            // Send OTP notification
            $user->notify(new SendOtpNotification($otp, 'password reset'));

            return $this->success([
                'phone' => $user->phone,
                'expires_in_minutes' => 10,
            ], 'Password reset OTP sent successfully.');

        } catch (\Exception $e) {
            return $this->error(500, 'Password reset request failed: ' . $e->getMessage());
        }
    }

    public function passwordResetConfirm(PasswordResetRequest $request)
    {
        try {
            $user = User::where('phone', $request->input('phone'))->first();

            if (!$user) {
                return $this->error(404, 'User not found.');
            }

            if (!$user->otp || !$user->otp_expires_at) {
                return $this->error(400, 'No OTP found for this user.');
            }

            if (Carbon::now()->gt($user->otp_expires_at)) {
                return $this->error(400, 'OTP has expired. Please request a new one.');
            }

            if (!Hash::check($request->input('code'), $user->otp)) {
                return $this->error(400, 'Invalid OTP code.');
            }

            // Update password and clear OTP
            $user->update([
                'password' => Hash::make($request->input('new_password')),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            return $this->success(
                null,
                'Password reset successfully.'
            );

        } catch (\Exception $e) {
            return $this->error(500, 'Password reset failed: ' . $e->getMessage());
        }
    }
}
