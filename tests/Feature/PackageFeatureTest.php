<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPackage;
use App\Services\PackageFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Package $package;
    protected PackageFeatureService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin', 'permissions' => []]);
        $superAdminRole = Role::create(['name' => 'super_admin', 'permissions' => []]);
        
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        // Create regular user
        $this->user = User::factory()->create([
            'account_type' => 'individual',
        ]);

        // Create a test package
        $this->package = Package::create([
            'name' => 'Test Package',
            'description' => 'A test package',
            'price' => 49.99,
            'duration_days' => 30,
            'active' => true,
        ]);

        $this->service = new PackageFeatureService();
    }

    // ========================================
    // PACKAGE FEATURES CRUD TESTS
    // ========================================

    public function test_admin_can_create_package_features()
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$this->package->id}/features", [
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 20,
            'unique_ads_allowed' => true,
            'unique_ads_limit' => 5,
            'caishha_ads_allowed' => false,
            'findit_ads_allowed' => true,
            'findit_ads_limit' => 3,
            'auction_ads_allowed' => false,
            'grants_seller_status' => true,
            'auto_verify_seller' => false,
            'grants_marketer_status' => false,
            'can_push_to_facebook' => true,
            'can_auto_republish' => true,
            'can_use_banner' => true,
            'can_use_background_color' => false,
            'images_per_ad_limit' => 15,
            'videos_per_ad_limit' => 2,
            'ad_duration_days' => 45,
            'max_ad_duration_days' => 90,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.package_id', $this->package->id)
            ->assertJsonPath('data.ad_types.normal.allowed', true)
            ->assertJsonPath('data.ad_types.normal.limit', 20)
            ->assertJsonPath('data.ad_types.unique.allowed', true)
            ->assertJsonPath('data.ad_types.caishha.allowed', false)
            ->assertJsonPath('data.role_features.grants_seller_status', true)
            ->assertJsonPath('data.ad_capabilities.can_push_to_facebook', true);

        $this->assertDatabaseHas('package_features', [
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 20,
        ]);
    }

    public function test_regular_user_cannot_create_package_features()
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/packages/{$this->package->id}/features", [
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 10,
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_create_duplicate_features()
    {
        // First, create features
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
        ]);

        // Try to create again
        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$this->package->id}/features", [
            'normal_ads_limit' => 30,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Features already exist for this package. Use PUT to update.');
    }

    public function test_admin_can_update_package_features()
    {
        // Create features first
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 10,
            'can_push_to_facebook' => false,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/v1/packages/{$this->package->id}/features", [
            'normal_ads_limit' => 25,
            'can_push_to_facebook' => true,
            'unique_ads_allowed' => true,
            'unique_ads_limit' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.ad_types.normal.limit', 25)
            ->assertJsonPath('data.ad_capabilities.can_push_to_facebook', true)
            ->assertJsonPath('data.ad_types.unique.allowed', true);
    }

    public function test_update_creates_features_if_not_exist()
    {
        // No features exist yet
        $response = $this->actingAs($this->admin)->putJson("/api/v1/packages/{$this->package->id}/features", [
            'normal_ads_limit' => 15,
            'grants_seller_status' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('package_features', [
            'package_id' => $this->package->id,
            'normal_ads_limit' => 15,
            'grants_seller_status' => true,
        ]);
    }

    public function test_admin_can_get_package_features()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 20,
            'unique_ads_allowed' => true,
            'unique_ads_limit' => 5,
            'grants_seller_status' => true,
            'can_push_to_facebook' => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/packages/{$this->package->id}/features");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.configured', true)
            ->assertJsonPath('data.ad_types.normal.allowed', true)
            ->assertJsonPath('data.ad_types.normal.limit', 20)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'package_id',
                    'configured',
                    'ad_types' => [
                        'normal' => ['allowed', 'limit', 'unlimited'],
                        'unique' => ['allowed', 'limit', 'unlimited'],
                        'caishha' => ['allowed', 'limit', 'unlimited'],
                        'findit' => ['allowed', 'limit', 'unlimited'],
                        'auction' => ['allowed', 'limit', 'unlimited'],
                    ],
                    'role_features',
                    'ad_capabilities',
                    'additional_features',
                    'summary',
                ],
            ]);
    }

    public function test_get_features_returns_defaults_when_not_configured()
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/packages/{$this->package->id}/features");

        $response->assertStatus(200)
            ->assertJsonPath('data.configured', false)
            ->assertJsonPath('data.features.ad_types.normal.allowed', true)
            ->assertJsonPath('data.features.ad_types.unique.allowed', false);
    }

    public function test_admin_can_delete_package_features()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/packages/{$this->package->id}/features");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('package_features', [
            'package_id' => $this->package->id,
        ]);
    }

    // ========================================
    // VALIDATION TESTS
    // ========================================

    public function test_validates_ad_limits_are_non_negative()
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$this->package->id}/features", [
            'normal_ads_limit' => -5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['normal_ads_limit']);
    }

    public function test_validates_max_duration_greater_than_default()
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$this->package->id}/features", [
            'ad_duration_days' => 60,
            'max_ad_duration_days' => 30,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_ad_duration_days']);
    }

    public function test_validates_auto_verify_requires_seller_status()
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/packages/{$this->package->id}/features", [
            'grants_seller_status' => false,
            'auto_verify_seller' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auto_verify_seller']);
    }

    // ========================================
    // USER PACKAGE FEATURE HELPERS TESTS
    // ========================================

    public function test_user_can_view_their_package_features()
    {
        // Create features and assign package to user
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 10,
            'unique_ads_allowed' => true,
            'unique_ads_limit' => 3,
            'can_push_to_facebook' => true,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/packages/my-features');

        $response->assertStatus(200)
            ->assertJsonPath('data.has_package', true)
            ->assertJsonPath('data.package.id', $this->package->id)
            ->assertJsonStructure([
                'data' => [
                    'has_package',
                    'package' => ['id', 'name'],
                    'features',
                ],
            ]);
    }

    public function test_user_without_package_gets_no_features()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/packages/my-features');

        $response->assertStatus(200)
            ->assertJsonPath('data.has_package', false)
            ->assertJsonPath('data.package', null);
    }

    // ========================================
    // CAPABILITY CHECK ENDPOINT TESTS
    // ========================================

    public function test_check_capability_publish_ad_allowed()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 10,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/packages/check-capability', [
            'capability' => 'publish_ad',
            'ad_type' => 'normal',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.capability', 'publish_ad')
            ->assertJsonPath('data.allowed', true);
    }

    public function test_check_capability_publish_ad_not_allowed()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'auction_ads_allowed' => false,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/packages/check-capability', [
            'capability' => 'publish_ad',
            'ad_type' => 'auction',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.allowed', false)
            ->assertJsonPath('data.reason', 'Your package does not allow auction ads');
    }

    public function test_check_capability_facebook_push()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'can_push_to_facebook' => true,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/packages/check-capability', [
            'capability' => 'push_to_facebook',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.allowed', true);
    }

    public function test_check_capability_bulk_upload_not_allowed()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'bulk_upload_allowed' => false,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/packages/check-capability', [
            'capability' => 'bulk_upload',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.allowed', false);
    }

    // ========================================
    // PACKAGE FEATURE SERVICE TESTS
    // ========================================

    public function test_service_validates_ad_creation_allowed()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 5,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $result = $this->service->validateAdCreation($this->user->fresh(), 'normal');

        $this->assertTrue($result['allowed']);
        $this->assertEquals(5, $result['remaining']);
    }

    public function test_service_validates_ad_creation_limit_reached()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 2,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        // Create 2 ads (at limit)
        for ($i = 0; $i < 2; $i++) {
            Ad::create([
                'user_id' => $this->user->id,
                'type' => 'normal',
                'title' => "Test Ad {$i}",
                'description' => 'Test description',
                'status' => 'published',
            ]);
        }

        $result = $this->service->validateAdCreation($this->user->fresh(), 'normal');

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertStringContainsString('reached your normal ads limit', $result['reason']);
    }

    public function test_service_validates_ad_features()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'can_push_to_facebook' => true,
            'can_use_banner' => false,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $result = $this->service->validateAdFeatures($this->user->fresh(), [
            'is_pushed_facebook' => true,
            'banner_image_id' => 123, // Not allowed
        ]);

        $this->assertFalse($result['allowed']);
        $this->assertArrayHasKey('banner_image_id', $result['denied_features']);
    }

    public function test_service_validates_media_limits()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'images_per_ad_limit' => 5,
            'videos_per_ad_limit' => 1,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $result = $this->service->validateMediaLimits($this->user->fresh(), 10, 0);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Maximum 5 images', $result['reason']);
    }

    // ========================================
    // ROLE UPGRADE TESTS
    // ========================================

    public function test_package_with_seller_status_upgrades_user()
    {
        // Create seller role
        Role::create(['name' => 'seller', 'permissions' => []]);

        PackageFeature::create([
            'package_id' => $this->package->id,
            'grants_seller_status' => true,
            'auto_verify_seller' => true,
        ]);

        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        // Apply features (normally done by observer or controller)
        $this->service->applyPackageFeatures($userPackage);

        $this->user->refresh();

        $this->assertTrue($this->user->hasRole('seller'));
        $this->assertTrue($this->user->seller_verified);
        $this->assertNotNull($this->user->seller_verified_at);
    }

    public function test_package_with_marketer_status_upgrades_user()
    {
        // Create marketer role
        Role::create(['name' => 'marketer', 'permissions' => []]);

        PackageFeature::create([
            'package_id' => $this->package->id,
            'grants_marketer_status' => true,
        ]);

        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $this->service->applyPackageFeatures($userPackage);

        $this->user->refresh();

        $this->assertTrue($this->user->hasRole('marketer'));
    }

    // ========================================
    // USAGE STATISTICS TESTS
    // ========================================

    public function test_service_provides_usage_statistics()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 10,
            'unique_ads_allowed' => true,
            'unique_ads_limit' => 5,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        // Create some ads
        Ad::create([
            'user_id' => $this->user->id,
            'type' => 'normal',
            'title' => 'Test Ad',
            'description' => 'Test',
            'status' => 'published',
        ]);

        $stats = $this->service->getUsageStatistics($this->user->fresh());

        $this->assertTrue($stats['has_package']);
        $this->assertEquals($this->package->id, $stats['package']['id']);
        $this->assertEquals(1, $stats['usage']['ad_types']['normal']['used']);
        $this->assertEquals(9, $stats['usage']['ad_types']['normal']['remaining']);
    }

    // ========================================
    // PACKAGE MODEL HELPER TESTS
    // ========================================

    public function test_package_is_ad_type_allowed_helper()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'auction_ads_allowed' => false,
        ]);

        $this->assertTrue($this->package->fresh()->isAdTypeAllowed('normal'));
        $this->assertFalse($this->package->fresh()->isAdTypeAllowed('auction'));
    }

    public function test_package_get_ad_type_limit_helper()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 25,
        ]);

        $this->assertEquals(25, $this->package->fresh()->getAdTypeLimit('normal'));
    }

    public function test_package_get_allowed_ad_types_helper()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'unique_ads_allowed' => true,
            'caishha_ads_allowed' => false,
            'findit_ads_allowed' => true,
            'auction_ads_allowed' => false,
        ]);

        $allowed = $this->package->fresh()->getAllowedAdTypes();

        $this->assertContains('normal', $allowed);
        $this->assertContains('unique', $allowed);
        $this->assertContains('findit', $allowed);
        $this->assertNotContains('caishha', $allowed);
        $this->assertNotContains('auction', $allowed);
    }

    public function test_package_get_feature_summary()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 10,
            'unique_ads_allowed' => false,
            'grants_seller_status' => true,
            'can_push_to_facebook' => true,
        ]);

        $summary = $this->package->fresh()->getFeatureSummary();

        $this->assertArrayHasKey('ad_types', $summary);
        $this->assertArrayHasKey('role_features', $summary);
        $this->assertArrayHasKey('ad_capabilities', $summary);
        $this->assertTrue($summary['ad_types']['normal']['allowed']);
        $this->assertEquals(10, $summary['ad_types']['normal']['limit']);
        $this->assertTrue($summary['role_features']['grants_seller_status']);
    }

    // ========================================
    // USER MODEL HELPER TESTS
    // ========================================

    public function test_user_can_publish_ad_type_helper()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'auction_ads_allowed' => false,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $this->assertTrue($this->user->fresh()->canPublishAdType('normal'));
        $this->assertFalse($this->user->fresh()->canPublishAdType('auction'));
    }

    public function test_user_without_package_can_only_publish_normal_ads()
    {
        // No package assigned
        $this->assertTrue($this->user->canPublishAdType('normal'));
        $this->assertFalse($this->user->canPublishAdType('unique'));
        $this->assertFalse($this->user->canPublishAdType('auction'));
    }

    public function test_user_get_remaining_ads_for_type()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 5,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        // Create 2 ads
        for ($i = 0; $i < 2; $i++) {
            Ad::create([
                'user_id' => $this->user->id,
                'type' => 'normal',
                'title' => "Ad {$i}",
                'description' => 'Test',
                'status' => 'published',
            ]);
        }

        $this->assertEquals(3, $this->user->fresh()->getRemainingAdsForType('normal'));
    }

    public function test_user_can_create_more_ads()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => 1,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $this->assertTrue($this->user->fresh()->canCreateMoreAds('normal'));

        // Create 1 ad (at limit)
        Ad::create([
            'user_id' => $this->user->id,
            'type' => 'normal',
            'title' => 'Ad 1',
            'description' => 'Test',
            'status' => 'published',
        ]);

        $this->assertFalse($this->user->fresh()->canCreateMoreAds('normal'));
    }

    public function test_user_package_feature_helpers()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'can_push_to_facebook' => true,
            'can_auto_republish' => false,
            'can_use_banner' => true,
            'images_per_ad_limit' => 20,
            'ad_duration_days' => 45,
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $user = $this->user->fresh();

        $this->assertTrue($user->canPushToFacebook());
        $this->assertFalse($user->canAutoRepublish());
        $this->assertTrue($user->canUseBanner());
        $this->assertEquals(20, $user->getImagesPerAdLimit());
        $this->assertEquals(45, $user->getDefaultAdDuration());
    }

    // ========================================
    // EDGE CASE TESTS
    // ========================================

    public function test_expired_package_features_not_applied()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'unique_ads_allowed' => true,
            'unique_ads_limit' => 10,
        ]);

        // Expired package
        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->subDays(60)->toDateString(),
            'end_date' => now()->subDays(30)->toDateString(), // Expired
            'active' => true,
        ]);

        // User should not have unique ads access
        $this->assertFalse($this->user->fresh()->canPublishAdType('unique'));
    }

    public function test_inactive_package_subscription_not_applied()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'unique_ads_allowed' => true,
        ]);

        // Inactive subscription
        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => false, // Deactivated
        ]);

        $this->assertFalse($this->user->fresh()->canPublishAdType('unique'));
    }

    public function test_unlimited_ads_when_limit_is_null()
    {
        PackageFeature::create([
            'package_id' => $this->package->id,
            'normal_ads_allowed' => true,
            'normal_ads_limit' => null, // Unlimited
        ]);

        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $remaining = $this->user->fresh()->getRemainingAdsForType('normal');
        
        $this->assertNull($remaining); // null means unlimited
        $this->assertTrue($this->user->fresh()->canCreateMoreAds('normal'));
    }
}
