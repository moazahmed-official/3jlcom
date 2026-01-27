<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SendOtpNotification;
use Carbon\Carbon;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test country
        Country::factory()->create([
            'id' => 1,
            'name' => 'Test Country',
            'code' => 'TC',
            'phone_code' => '+1',
        ]);
    }

    public function test_user_can_register_successfully()
    {
        Notification::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'country_id' => 1,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'individual',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'user_id',
                        'phone',
                        'expires_in_minutes'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ]);

        // Assert user was created
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'account_type' => 'individual',
        ]);

        // Assert OTP notification was sent
        Notification::assertSentTo(
            User::where('email', 'john@example.com')->first(),
            SendOtpNotification::class
        );
    }

    public function test_user_can_verify_otp_successfully()
    {
        // Create user with OTP
        $otp = '123456';
        $user = User::factory()->unverified()->create([
            'phone' => '+1234567890',
            'otp' => Hash::make($otp),
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->putJson('/api/v1/auth/verify', [
            'phone' => '+1234567890',
            'code' => $otp,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'token',
                        'token_type',
                        'user'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ]);

        // Assert user is verified and OTP cleared
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->otp);
        $this->assertNull($user->otp_expires_at);
    }

    public function test_user_can_request_password_reset()
    {
        Notification::fake();

        $user = User::factory()->create([
            'phone' => '+1234567890',
        ]);

        $response = $this->postJson('/api/v1/auth/password/reset-request', [
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'phone',
                        'expires_in_minutes'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ]);

        // Assert OTP was set
        $user->refresh();
        $this->assertNotNull($user->otp);
        $this->assertNotNull($user->otp_expires_at);

        // Assert notification was sent
        Notification::assertSentTo($user, SendOtpNotification::class);
    }

    public function test_user_can_reset_password_with_otp()
    {
        $otp = '123456';
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'password' => Hash::make('oldpassword'),
            'otp' => Hash::make($otp),
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->putJson('/api/v1/auth/password/reset', [
            'phone' => '+1234567890',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
            'code' => $otp,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                ])
                ->assertJson([
                    'status' => 'success',
                ]);

        // Assert password was changed and OTP cleared
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertNull($user->otp);
        $this->assertNull($user->otp_expires_at);
    }

    public function test_expired_otp_is_rejected()
    {
        $otp = '123456';
        $user = User::factory()->unverified()->create([
            'phone' => '+1234567890',
            'otp' => Hash::make($otp),
            'otp_expires_at' => Carbon::now()->subMinutes(1), // Expired
        ]);

        $response = $this->putJson('/api/v1/auth/verify', [
            'phone' => '+1234567890',
            'code' => $otp,
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'OTP has expired. Please request a new one.',
                ]);
    }

    public function test_invalid_otp_is_rejected()
    {
        $user = User::factory()->unverified()->create([
            'phone' => '+1234567890',
            'otp' => Hash::make('123456'),
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->putJson('/api/v1/auth/verify', [
            'phone' => '+1234567890',
            'code' => '654321', // Wrong OTP
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid OTP code.',
                ]);
    }
}