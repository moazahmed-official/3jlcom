<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateUserTest extends TestCase
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

        // Create admin role
        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'admin',
            'permissions' => json_encode(['users.create', 'users.read.any', 'users.update.any', 'users.delete.any']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create individual role (non-admin)
        DB::table('roles')->insert([
            'id' => 2,
            'name' => 'individual',
            'permissions' => json_encode(['users.read.own', 'users.update.own']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Helper to create an admin user and authenticate.
     */
    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'phone' => '+962791000000',
            'country_id' => 1,
            'account_type' => 'admin',
        ]);

        // Attach admin role
        DB::table('user_role')->insert([
            'user_id' => $admin->id,
            'role_id' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    /**
     * Helper to create an individual (non-admin) user and authenticate.
     */
    protected function actingAsIndividual(): User
    {
        $user = User::factory()->create([
            'name' => 'Regular User',
            'phone' => '+962792000000',
            'country_id' => 1,
            'account_type' => 'individual',
        ]);

        // Attach individual role
        DB::table('user_role')->insert([
            'user_id' => $user->id,
            'role_id' => 2,
        ]);

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    #[Test]
    public function test_admin_can_create_user_successfully(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+962791234567',
            'country_id' => 1,
            'account_type' => 'individual',
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'phone',
                    'account_type',
                    'is_verified',
                    'created_at',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => [
                    'name' => 'John Doe',
                    'phone' => '+962791234567',
                    'account_type' => 'individual',
                    'is_verified' => false,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '+962791234567',
            'name' => 'John Doe',
            'country_id' => 1,
            'account_type' => 'individual',
        ]);
    }

    #[Test]
    public function test_admin_can_create_user_with_minimal_fields(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'phone' => '+962793456789',
            'country_id' => 1,
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Jane Doe',
                    'phone' => '+962793456789',
                    'account_type' => 'individual', // default
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '+962793456789',
            'account_type' => 'individual',
        ]);
    }

    #[Test]
    public function test_create_user_fails_when_name_is_missing(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'email' => 'test@example.com',
            'phone' => '+962791234567',
            'country_id' => 1,
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'errors' => ['name'],
            ])
            ->assertJson([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed',
            ]);
    }

    #[Test]
    public function test_create_user_fails_when_phone_is_missing(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john.doe2@example.com',
            'country_id' => 1,
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'code' => 422,
            ])
            ->assertJsonPath('errors.phone.0', 'The phone field is required.');
    }

    #[Test]
    public function test_create_user_fails_when_country_id_is_invalid(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john.doe3@example.com',
            'phone' => '+962791234567',
            'country_id' => 999, // non-existent
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('errors.country_id.0', 'The selected country does not exist.');
    }

    #[Test]
    public function test_create_user_fails_when_phone_is_duplicate(): void
    {
        $this->actingAsAdmin();

        // Create existing user with the same phone
        User::factory()->create([
            'phone' => '+962791234567',
            'email' => 'existing@example.com',
            'country_id' => 1,
        ]);

        $payload = [
            'name' => 'Another User',
            'email' => 'another@example.com',
            'phone' => '+962791234567', // duplicate
            'country_id' => 1,
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'This phone number is already registered.');
    }

    #[Test]
    public function test_create_user_fails_with_invalid_account_type(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john.doe4@example.com',
            'phone' => '+962791234567',
            'country_id' => 1,
            'account_type' => 'invalid_type',
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('errors.account_type.0', 'The selected account type is invalid.');
    }

    #[Test]
    public function test_unauthenticated_user_cannot_create_user(): void
    {
        $payload = [
            'name' => 'John Doe',
            'phone' => '+962791234567',
            'country_id' => 1,
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthenticated',
            ]);
    }

    #[Test]
    public function test_non_admin_user_cannot_create_user(): void
    {
        $this->actingAsIndividual();

        $payload = [
            'name' => 'John Doe',
            'phone' => '+962791234567',
            'country_id' => 1,
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'You do not have permission to create users.',
            ]);
    }
}
