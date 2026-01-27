<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'display_name' => 'Administrator', 'permissions' => json_encode(['users.create', 'users.read.any', 'users.update.any', 'users.delete.any']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'super_admin', 'display_name' => 'Super Administrator', 'permissions' => json_encode(['*']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'individual', 'display_name' => 'Individual User', 'permissions' => json_encode(['users.read.own', 'users.update.own']), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create country for user creation
        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Jordan',
            'code' => 'JO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Helper to create and authenticate a super admin user.
     */
    protected function actingAsSuperAdmin(): User
    {
        $admin = User::factory()->create([
            'name' => 'Super Admin User',
            'phone' => '+962791000000',
            'country_id' => 1,
            'account_type' => 'admin',
        ]);

        // Attach super_admin role
        DB::table('user_role')->insert([
            'user_id' => $admin->id,
            'role_id' => 2,
        ]);

        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    /**
     * Helper to create and authenticate an admin user.
     */
    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'phone' => '+962791000001',
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
     * Helper to create an individual user.
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
            'role_id' => 3,
        ]);

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    #[Test]
    public function test_can_list_roles(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'page',
                    'per_page',
                    'total',
                    'items' => [
                        '*' => [
                            'id',
                            'name',
                            'display_name',
                            'permissions',
                            'users_count',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Roles retrieved successfully',
            ]);
    }

    #[Test]
    public function test_super_admin_can_create_role(): void
    {
        $this->actingAsSuperAdmin();

        $payload = [
            'name' => 'moderator',
            'display_name' => 'Content Moderator',
            'permissions' => ['content.moderate', 'content.review'],
        ];

        $response = $this->postJson('/api/v1/roles', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Role created successfully',
                'data' => [
                    'name' => 'moderator',
                    'display_name' => 'Content Moderator',
                ],
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'moderator',
            'display_name' => 'Content Moderator',
        ]);
    }

    #[Test]
    public function test_admin_cannot_create_role(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'moderator',
            'display_name' => 'Content Moderator',
            'permissions' => ['content.moderate'],
        ];

        $response = $this->postJson('/api/v1/roles', $payload);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'You do not have permission to create roles.',
            ]);
    }

    #[Test]
    public function test_can_show_role(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/roles/1');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Role retrieved successfully',
                'data' => [
                    'id' => 1,
                    'name' => 'admin',
                ],
            ]);
    }

    #[Test]
    public function test_super_admin_can_update_role(): void
    {
        $this->actingAsSuperAdmin();

        $payload = [
            'display_name' => 'Updated Individual User',
            'permissions' => ['users.read.own', 'users.update.own', 'content.create'],
        ];

        $response = $this->putJson('/api/v1/roles/3', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => [
                    'display_name' => 'Updated Individual User',
                ],
            ]);
    }

    #[Test]
    public function test_cannot_update_system_roles(): void
    {
        $this->actingAsSuperAdmin();

        $payload = [
            'display_name' => 'Hacked Admin',
        ];

        $response = $this->putJson('/api/v1/roles/1', $payload);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'You do not have permission to update this role.',
            ]);
    }

    #[Test]
    public function test_super_admin_can_delete_role(): void
    {
        $this->actingAsSuperAdmin();

        // Create a new role that we can delete
        $role = Role::create([
            'name' => 'temp_role',
            'display_name' => 'Temporary Role',
            'permissions' => ['temp.permission'],
        ]);

        $response = $this->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Role deleted successfully',
            ]);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    #[Test]
    public function test_cannot_delete_system_roles(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->deleteJson('/api/v1/roles/1');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'Cannot delete system role',
            ]);
    }

    #[Test]
    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $this->actingAsSuperAdmin();
        
        // Create a user and assign to individual role (id: 3)
        $user = User::factory()->create(['country_id' => 1]);
        DB::table('user_role')->insert([
            'user_id' => $user->id,
            'role_id' => 3,
        ]);

        $response = $this->deleteJson('/api/v1/roles/3'); // individual role has users

        $response->assertStatus(409)
            ->assertJson([
                'status' => 'error',
                'code' => 409,
                'message' => 'Cannot delete role with assigned users',
            ]);
    }

    #[Test]
    public function test_admin_can_assign_roles_to_user(): void
    {
        $this->actingAsAdmin();
        
        $user = User::factory()->create([
            'country_id' => 1,
        ]);

        $payload = [
            'roles' => ['individual', 'admin'],
        ];

        $response = $this->postJson("/api/v1/users/{$user->id}/roles", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Roles assigned successfully',
                'data' => [
                    'user_id' => $user->id,
                    'account_type' => 'admin',
                ],
            ])
            ->assertJsonCount(2, 'data.roles');

        // Check database
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => 1, // admin
        ]);
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => 3, // individual
        ]);
    }

    #[Test]
    public function test_can_get_user_roles(): void
    {
        $this->actingAsAdmin();
        
        $user = User::factory()->create([
            'country_id' => 1,
        ]);
        
        // Assign roles directly
        DB::table('user_role')->insert([
            ['user_id' => $user->id, 'role_id' => 1],
            ['user_id' => $user->id, 'role_id' => 3],
        ]);

        $response = $this->getJson("/api/v1/users/{$user->id}/roles");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User roles retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                ],
            ])
            ->assertJsonCount(2, 'data.roles');
    }

    #[Test]
    public function test_individual_user_cannot_assign_roles(): void
    {
        $this->actingAsIndividual();
        
        $user = User::factory()->create([
            'country_id' => 1,
        ]);

        $payload = [
            'roles' => ['individual'],
        ];

        $response = $this->postJson("/api/v1/users/{$user->id}/roles", $payload);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'You do not have permission to manage user roles.',
            ]);
    }

    #[Test]
    public function test_assign_roles_validates_role_names(): void
    {
        $this->actingAsAdmin();
        
        $user = User::factory()->create([
            'country_id' => 1,
        ]);

        $payload = [
            'roles' => ['individual', 'nonexistent_role'],
        ];

        $response = $this->postJson("/api/v1/users/{$user->id}/roles", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['roles.1']);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_roles(): void
    {
        $response = $this->getJson('/api/v1/roles');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthenticated',
            ]);
    }
}