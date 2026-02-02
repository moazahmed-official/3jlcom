<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin', 'permissions' => []]);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        // Create regular user
        $this->user = User::factory()->create();

        // Create a test package
        $this->package = Package::create([
            'name' => 'Basic Package',
            'description' => 'A basic package for testing',
            'price' => 29.99,
            'duration_days' => 30,
            'features' => [
                'ads_limit' => 10,
                'featured_ads' => 2,
                'priority_support' => false,
            ],
            'active' => true,
        ]);
    }

    // =====================
    // PUBLIC ENDPOINTS TESTS
    // =====================

    public function test_anyone_can_list_active_packages()
    {
        // Create additional packages
        Package::create([
            'name' => 'Premium Package',
            'price' => 99.99,
            'duration_days' => 30,
            'active' => true,
        ]);

        Package::create([
            'name' => 'Inactive Package',
            'price' => 49.99,
            'duration_days' => 30,
            'active' => false,
        ]);

        $response = $this->getJson('/api/v1/packages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'page',
                    'per_page',
                    'total',
                    'items' => [
                        '*' => ['id', 'name', 'description', 'price', 'duration_days', 'features', 'active']
                    ]
                ]
            ]);

        // Should only see active packages (2, not 3)
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_anyone_can_view_active_package()
    {
        $response = $this->getJson("/api/v1/packages/{$this->package->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->package->id)
            ->assertJsonPath('data.name', 'Basic Package')
            ->assertJsonPath('data.price', 29.99);
    }

    public function test_cannot_view_inactive_package_as_guest()
    {
        $inactivePackage = Package::create([
            'name' => 'Hidden Package',
            'price' => 199.99,
            'duration_days' => 365,
            'active' => false,
        ]);

        $response = $this->getJson("/api/v1/packages/{$inactivePackage->id}");

        $response->assertStatus(404);
    }

    // =====================
    // ADMIN CRUD TESTS
    // =====================

    public function test_admin_can_create_package()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/packages', [
            'name' => 'New Premium Package',
            'description' => 'Premium features for power users',
            'price' => 149.99,
            'duration_days' => 90,
            'features' => [
                'ads_limit' => 50,
                'featured_ads' => 10,
                'priority_support' => true,
                'analytics' => true,
            ],
            'active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Premium Package')
            ->assertJsonPath('data.price', 149.99);

        $this->assertDatabaseHas('packages', ['name' => 'New Premium Package']);
    }

    public function test_regular_user_cannot_create_package()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/packages', [
            'name' => 'Unauthorized Package',
            'price' => 99.99,
            'duration_days' => 30,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_package()
    {
        $response = $this->actingAs($this->admin)->putJson("/api/v1/packages/{$this->package->id}", [
            'name' => 'Updated Package Name',
            'price' => 39.99,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Package Name')
            ->assertJsonPath('data.price', 39.99);

        $this->assertDatabaseHas('packages', [
            'id' => $this->package->id,
            'name' => 'Updated Package Name',
        ]);
    }

    public function test_admin_can_delete_package_without_subscribers()
    {
        $emptyPackage = Package::create([
            'name' => 'To Be Deleted',
            'price' => 9.99,
            'duration_days' => 7,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/packages/{$emptyPackage->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('packages', ['id' => $emptyPackage->id]);
    }

    public function test_cannot_delete_package_with_active_subscribers()
    {
        // Assign package to user
        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/packages/{$this->package->id}");

        $response->assertStatus(409)
            ->assertJsonPath('code', 409);

        $this->assertDatabaseHas('packages', ['id' => $this->package->id]);
    }

    // =====================
    // PACKAGE ASSIGNMENT TESTS
    // =====================

    public function test_admin_can_assign_package_to_user()
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$this->package->id}/assign", [
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.package_id', $this->package->id)
            ->assertJsonPath('data.is_valid', true);

        $this->assertDatabaseHas('user_packages', [
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'active' => true,
        ]);
    }

    public function test_cannot_assign_duplicate_active_package()
    {
        // First assignment
        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'active' => true,
        ]);

        // Try to assign again
        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$this->package->id}/assign", [
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(409);
    }

    public function test_cannot_assign_inactive_package()
    {
        $inactivePackage = Package::create([
            'name' => 'Inactive Package',
            'price' => 49.99,
            'duration_days' => 30,
            'active' => false,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$inactivePackage->id}/assign", [
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(400);
    }

    // =====================
    // USER PACKAGES TESTS
    // =====================

    public function test_user_can_view_own_packages()
    {
        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/packages/my-packages');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.package_id', $this->package->id);
    }

    public function test_user_cannot_view_other_users_packages()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/v1/users/{$otherUser->id}/packages");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_users_packages()
    {
        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/users/{$this->user->id}/packages");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    // =====================
    // STATISTICS TESTS
    // =====================

    public function test_admin_can_view_package_statistics()
    {
        // Create some subscriptions
        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/packages/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_packages',
                    'active_packages',
                    'inactive_packages',
                    'free_packages',
                    'paid_packages',
                    'total_subscriptions',
                    'active_subscriptions',
                    'expired_subscriptions',
                    'revenue_potential',
                ]
            ]);
    }

    public function test_regular_user_cannot_view_statistics()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/packages/stats');

        $response->assertStatus(403);
    }

    // =====================
    // VALIDATION TESTS
    // =====================

    public function test_create_package_validation()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/packages', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'duration_days']);
    }

    public function test_create_package_price_validation()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/packages', [
            'name' => 'Test Package',
            'price' => -10, // Invalid negative price
            'duration_days' => 30,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_assign_package_requires_valid_user()
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$this->package->id}/assign", [
            'user_id' => 99999, // Non-existent user
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    // =====================
    // FILTER TESTS
    // =====================

    public function test_can_filter_packages_by_price_range()
    {
        Package::create(['name' => 'Cheap', 'price' => 9.99, 'duration_days' => 7, 'active' => true]);
        Package::create(['name' => 'Expensive', 'price' => 199.99, 'duration_days' => 365, 'active' => true]);

        $response = $this->getJson('/api/v1/packages?price_min=20&price_max=100');

        $response->assertStatus(200);
        
        // Should only include Basic Package (29.99)
        $packages = collect($response->json('data.items'));
        $this->assertTrue($packages->every(fn($p) => $p['price'] >= 20 && $p['price'] <= 100));
    }

    public function test_can_filter_free_packages()
    {
        Package::create(['name' => 'Free Plan', 'price' => 0, 'duration_days' => 30, 'active' => true]);

        $response = $this->getJson('/api/v1/packages?free=true');

        $response->assertStatus(200);
        
        $packages = collect($response->json('data.items'));
        $this->assertTrue($packages->every(fn($p) => $p['price'] == 0));
    }

    public function test_admin_can_view_inactive_packages()
    {
        Package::create(['name' => 'Inactive', 'price' => 49.99, 'duration_days' => 30, 'active' => false]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/packages?active=false');

        $response->assertStatus(200);
        
        $packages = collect($response->json('data.items'));
        $this->assertTrue($packages->contains(fn($p) => $p['active'] === false));
    }
}
