<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_successful()
    {
        $user = User::factory()->create([
            'phone' => '+201001234567',
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this->postJson('/v1/auth/login', [
            'phone' => $user->phone,
            'password' => 'secret-password',
        ]);

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
                    ],
                ],
            ]);
    }

    public function test_login_validation_error()
    {
        $response = $this->postJson('/v1/auth/login', [
            'phone' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }

    public function test_login_invalid_credentials()
    {
        $user = User::factory()->create([
            'phone' => '+201009998887',
            'password' => bcrypt('right-password'),
        ]);

        $response = $this->postJson('/v1/auth/login', [
            'phone' => $user->phone,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'code' => 401,
                'message' => 'Invalid credentials',
            ]);
    }
}
