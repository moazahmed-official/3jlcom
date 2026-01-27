<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleAssignmentAccountTypeAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'display_name' => 'Administrator', 'permissions' => json_encode(['*']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'dealer', 'display_name' => 'Dealer', 'permissions' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
        ]);

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
        $admin = User::factory()->create(['country_id' => 1, 'account_type' => 'admin']);
        DB::table('user_role')->insert(['user_id' => $admin->id, 'role_id' => 1]);
        Sanctum::actingAs($admin, ['*']);
        return $admin;
    }

    #[Test]
    public function test_audit_record_created_on_account_type_change(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create(['country_id' => 1, 'account_type' => 'individual']);

        $response = $this->postJson("/api/v1/users/{$user->id}/roles", ['roles' => ['dealer']]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_type_changes', [
            'user_id' => $user->id,
            'old_account_type' => 'individual',
            'new_account_type' => 'business',
        ]);
    }
}
