<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleAssignmentAccountTypeMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'display_name' => 'Administrator', 'permissions' => json_encode(['*']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'super_admin', 'display_name' => 'Super Administrator', 'permissions' => json_encode(['*']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'individual', 'display_name' => 'Individual', 'permissions' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'dealer', 'display_name' => 'Dealer', 'permissions' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'company', 'display_name' => 'Company', 'permissions' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create country
        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Testland',
            'code' => 'TL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'country_id' => 1,
            'account_type' => 'admin',
        ]);

        DB::table('user_role')->insert([
            'user_id' => $admin->id,
            'role_id' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    #[Test]
    public function test_assign_dealer_sets_business_account_type(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create(['country_id' => 1, 'account_type' => 'individual']);

        $payload = ['roles' => ['dealer']];

        $response = $this->postJson("/api/v1/users/{$user->id}/roles", $payload);

        $response->assertStatus(200)
            ->assertJson([ 'status' => 'success', 'message' => 'Roles assigned successfully', 'data' => ['account_type' => 'business'] ]);

        $this->assertDatabaseHas('user_role', [ 'user_id' => $user->id, 'role_id' => 4 ]);
        $this->assertDatabaseHas('users', [ 'id' => $user->id, 'account_type' => 'business' ]);
    }

    #[Test]
    public function test_admin_role_overrides_individual_to_admin(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create(['country_id' => 1, 'account_type' => 'individual']);

        $payload = ['roles' => ['individual', 'admin']];

        $response = $this->postJson("/api/v1/users/{$user->id}/roles", $payload);

        $response->assertStatus(200)
            ->assertJson([ 'status' => 'success', 'data' => ['account_type' => 'admin'] ]);

        $this->assertDatabaseHas('user_role', [ 'user_id' => $user->id, 'role_id' => 1 ]);
        $this->assertDatabaseHas('users', [ 'id' => $user->id, 'account_type' => 'admin' ]);
    }
}
