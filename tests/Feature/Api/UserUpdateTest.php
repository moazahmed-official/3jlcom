<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserUpdateTest extends TestCase
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

        // Create roles
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'display_name' => 'Administrator', 'permissions' => json_encode(['users.update.any']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'individual', 'display_name' => 'Individual User', 'permissions' => json_encode(['users.update.own']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Helper to create and authenticate an admin user.
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
     * Helper to create and authenticate an individual user.
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
    public function test_user_can_update_own_profile(): void
    {
        $user = $this->actingAsIndividual();

        $payload = [
            'name' => 'Updated Name',
            'phone' => '+962793456789',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                    'phone' => '+962793456789',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '+962793456789',
        ]);
    }

    #[Test]
    public function test_admin_can_update_any_user(): void
    {
        $this->actingAsAdmin();
        
        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'phone' => '+962794567890',
            'country_id' => 1,
        ]);

        $payload = [
            'name' => 'Admin Updated Name',
            'account_type' => 'dealer',
        ];

        $response = $this->putJson("/api/v1/users/{$targetUser->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => [
                    'name' => 'Admin Updated Name',
                    'account_type' => 'dealer',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Admin Updated Name',
            'account_type' => 'dealer',
        ]);
    }

    #[Test]
    public function test_user_cannot_update_other_users(): void
    {
        $this->actingAsIndividual();
        
        $otherUser = User::factory()->create([
            'name' => 'Other User',
            'phone' => '+962795678901',
            'country_id' => 1,
        ]);

        $payload = [
            'name' => 'Hacker Name',
        ];

        $response = $this->putJson("/api/v1/users/{$otherUser->id}", $payload);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'You do not have permission to update this user.',
            ]);

        // Verify the user was not updated
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'name' => 'Other User',
        ]);
    }

    #[Test]
    public function test_update_user_validates_email_uniqueness(): void
    {
        $user = $this->actingAsIndividual();
        
        // Create another user with an email
        User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '+962796789012',
            'country_id' => 1,
        ]);

        $payload = [
            'email' => 'existing@example.com', // duplicate email
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_update_user_validates_phone_uniqueness(): void
    {
        $user = $this->actingAsIndividual();
        
        // Create another user with a phone
        User::factory()->create([
            'phone' => '+962797890123',
            'country_id' => 1,
        ]);

        $payload = [
            'phone' => '+962797890123', // duplicate phone
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    #[Test]
    public function test_update_user_can_change_password(): void
    {
        $user = $this->actingAsIndividual();
        $originalPasswordHash = $user->password;

        $payload = [
            'password' => 'new-password-123',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User updated successfully',
            ]);

        // Verify password was changed and hashed
        $user->refresh();
        $this->assertNotEquals($originalPasswordHash, $user->password);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    #[Test]
    public function test_update_user_validates_country_exists(): void
    {
        $user = $this->actingAsIndividual();

        $payload = [
            'country_id' => 999, // non-existent country
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country_id']);
    }

    #[Test]
    public function test_update_user_validates_account_type(): void
    {
        $user = $this->actingAsIndividual();

        $payload = [
            'account_type' => 'invalid_type',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_type']);
    }

    #[Test]
    public function test_update_user_validates_password_minimum_length(): void
    {
        $user = $this->actingAsIndividual();

        $payload = [
            'password' => '123', // too short
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_update_users(): void
    {
        $user = User::factory()->create(['country_id' => 1]);

        $payload = [
            'name' => 'Hacker Name',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthenticated',
            ]);
    }

    #[Test]
    public function test_partial_update_only_updates_provided_fields(): void
    {
        $user = $this->actingAsIndividual();
        $originalName = $user->name;
        $originalPhone = $user->phone;

        $payload = [
            'name' => 'Only Name Updated',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $payload);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Only Name Updated', $user->name);
        $this->assertEquals($originalPhone, $user->phone); // Should remain unchanged
    }
}