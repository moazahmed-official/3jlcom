<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a country for testing
        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Jordan',
            'code' => 'JO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function test_user_can_login_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'phone' => '+962791234567',
            'password' => Hash::make('password123'),
            'country_id' => 1,
        ]);

        $payload = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'phone',
                        'account_type',
                        'is_verified',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Authenticated',
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    #[Test]
    public function test_user_can_login_with_phone_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test2@example.com',
            'phone' => '+962791234568',
            'password' => Hash::make('password123'),
            'country_id' => 1,
        ]);

        $payload = [
            'phone' => '+962791234568',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Authenticated',
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    #[Test]
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
            'country_id' => 1,
        ]);

        $payload = [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'code' => 401,
                'message' => 'Invalid credentials',
            ]);
    }

    #[Test]
    public function test_login_fails_without_email_or_phone(): void
    {
        $payload = [
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed',
            ])
            ->assertJsonPath('errors.phone.0', 'The phone or email field is required.')
            ->assertJsonPath('errors.email.0', 'The phone or email field is required.');
    }
}