<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserDeleteTest extends TestCase
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
            ['id' => 1, 'name' => 'admin', 'display_name' => 'Administrator', 'permissions' => json_encode(['users.delete.any']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'super_admin', 'display_name' => 'Super Administrator', 'permissions' => json_encode(['*']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'individual', 'display_name' => 'Individual User', 'permissions' => json_encode(['users.read.own']), 'created_at' => now(), 'updated_at' => now()],
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
     * Helper to create and authenticate a super admin user.
     */
    protected function actingAsSuperAdmin(): User
    {
        $admin = User::factory()->create([
            'name' => 'Super Admin User',
            'phone' => '+962791000001',
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
            'role_id' => 3,
        ]);

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    #[Test]
    public function test_admin_can_delete_regular_user(): void
    {
        $this->actingAsAdmin();
        
        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'phone' => '+962794567890',
            'country_id' => 1,
        ]);

        // Assign individual role to target user
        DB::table('user_role')->insert([
            'user_id' => $targetUser->id,
            'role_id' => 3,
        ]);

        $response = $this->deleteJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User deleted successfully',
            ]);

        // Verify user was deleted
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);

        // Verify roles were detached
        $this->assertDatabaseMissing('user_role', [
            'user_id' => $targetUser->id,
        ]);
    }

    #[Test]
    public function test_super_admin_can_delete_admin_user(): void
    {
        $this->actingAsSuperAdmin();
        
        $adminUser = User::factory()->create([
            'name' => 'Admin User To Delete',
            'phone' => '+962794567891',
            'country_id' => 1,
        ]);

        // Assign admin role to target user
        DB::table('user_role')->insert([
            'user_id' => $adminUser->id,
            'role_id' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/users/{$adminUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User deleted successfully',
            ]);

        // Verify user was deleted
        $this->assertDatabaseMissing('users', [
            'id' => $adminUser->id,
        ]);
    }

    #[Test]
    public function test_admin_cannot_delete_super_admin_user(): void
    {
        $this->actingAsAdmin();
        
        $superAdminUser = User::factory()->create([
            'name' => 'Super Admin User',
            'phone' => '+962794567892',
            'country_id' => 1,
        ]);

        // Assign super_admin role to target user
        DB::table('user_role')->insert([
            'user_id' => $superAdminUser->id,
            'role_id' => 2,
        ]);

        $response = $this->deleteJson("/api/v1/users/{$superAdminUser->id}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'Cannot delete super admin user',
            ]);

        // Verify user was not deleted
        $this->assertDatabaseHas('users', [
            'id' => $superAdminUser->id,
        ]);
    }

    #[Test]
    public function test_user_cannot_delete_self(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->deleteJson("/api/v1/users/{$admin->id}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'You cannot delete your own account',
            ]);

        // Verify user was not deleted
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    #[Test]
    public function test_individual_user_cannot_delete_users(): void
    {
        $this->actingAsIndividual();
        
        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'phone' => '+962794567893',
            'country_id' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'You do not have permission to delete users',
            ]);

        // Verify user was not deleted
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
        ]);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_delete_users(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'phone' => '+962794567894',
            'country_id' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'code' => 401,
                'message' => 'Unauthenticated',
            ]);

        // Verify user was not deleted
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
        ]);
    }

    #[Test]
    public function test_delete_nonexistent_user_returns_404(): void
    {
        $this->actingAsAdmin();

        $response = $this->deleteJson('/api/v1/users/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function test_super_admin_can_delete_super_admin_user(): void
    {
        $this->actingAsSuperAdmin();
        
        $otherSuperAdmin = User::factory()->create([
            'name' => 'Other Super Admin',
            'phone' => '+962794567895',
            'country_id' => 1,
        ]);

        // Assign super_admin role to target user
        DB::table('user_role')->insert([
            'user_id' => $otherSuperAdmin->id,
            'role_id' => 2,
        ]);

        $response = $this->deleteJson("/api/v1/users/{$otherSuperAdmin->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User deleted successfully',
            ]);

        // Verify user was deleted
        $this->assertDatabaseMissing('users', [
            'id' => $otherSuperAdmin->id,
        ]);
    }

    #[Test]
    public function test_delete_user_removes_role_associations(): void
    {
        $this->actingAsAdmin();
        
        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'phone' => '+962794567896',
            'country_id' => 1,
        ]);

        // Assign multiple roles to target user
        DB::table('user_role')->insert([
            ['user_id' => $targetUser->id, 'role_id' => 1],
            ['user_id' => $targetUser->id, 'role_id' => 3],
        ]);

        // Verify roles are assigned
        $this->assertDatabaseHas('user_role', [
            'user_id' => $targetUser->id,
            'role_id' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200);

        // Verify all role associations were removed
        $this->assertDatabaseMissing('user_role', [
            'user_id' => $targetUser->id,
        ]);

        // Verify user was deleted
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }
}