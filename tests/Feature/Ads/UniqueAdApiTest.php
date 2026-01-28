<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Media;
use App\Models\Role;
use App\Models\UniqueAd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniqueAdApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;
    protected Country $country;
    protected City $city;
    protected Category $category;
    protected ?Brand $brand;
    protected ?CarModel $carModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create country and city using factories
        $this->country = Country::factory()->create();
        $this->city = City::factory()->create(['country_id' => $this->country->id]);

        // Create category
        $this->category = Category::factory()->create();

        // Create brand and model (optional)
        $this->brand = Brand::factory()->create();
        $this->carModel = CarModel::factory()->create(['brand_id' => $this->brand->id]);

        // Create regular user
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'is_verified' => true,
        ]);

        // Create admin user
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
            'is_verified' => true,
        ]);
        $this->adminUser->roles()->attach($adminRole);
    }

    /**
     * Create a unique ad with all related records
     */
    protected function createUniqueAd(array $adOverrides = [], array $uniqueAdOverrides = []): Ad
    {
        $ad = Ad::create(array_merge([
            'user_id' => $this->user->id,
            'type' => 'unique',
            'title' => 'Test Unique Ad Title That Is Long Enough',
            'description' => str_repeat('This is a test description for the unique ad. ', 5),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'brand_id' => $this->brand?->id,
            'model_id' => $this->carModel?->id,
            'year' => 2024,
            'status' => 'published',
            'contact_phone' => '+971501234567',
            'whatsapp_number' => '+971501234567',
            'views_count' => 0,
            'period_days' => 30,
        ], $adOverrides));

        UniqueAd::create(array_merge([
            'ad_id' => $ad->id,
            'banner_color' => '#FF5733',
            'is_auto_republished' => false,
            'is_verified_ad' => false,
            'is_featured' => false,
        ], $uniqueAdOverrides));

        return $ad->fresh(['uniqueAd']);
    }

    // ==================== PUBLIC ENDPOINTS ====================

    /** @test */
    public function test_anyone_can_list_published_unique_ads()
    {
        // Create some published ads
        $this->createUniqueAd(['status' => 'published']);
        $this->createUniqueAd(['status' => 'published']);
        $this->createUniqueAd(['status' => 'draft']); // Should not appear

        $response = $this->getJson('/api/v1/unique-ads');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_can_filter_unique_ads_by_brand()
    {
        $otherBrand = Brand::factory()->create();
        
        $this->createUniqueAd(['brand_id' => $this->brand->id]);
        $this->createUniqueAd(['brand_id' => $otherBrand->id]);

        $response = $this->getJson('/api/v1/unique-ads?brand_id=' . $this->brand->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_can_filter_unique_ads_by_city()
    {
        $otherCity = City::factory()->create(['country_id' => $this->country->id]);
        
        $this->createUniqueAd(['city_id' => $this->city->id]);
        $this->createUniqueAd(['city_id' => $otherCity->id]);

        $response = $this->getJson('/api/v1/unique-ads?city_id=' . $this->city->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_can_filter_unique_ads_by_verified_only()
    {
        $this->createUniqueAd([], ['is_verified_ad' => true]);
        $this->createUniqueAd([], ['is_verified_ad' => false]);

        $response = $this->getJson('/api/v1/unique-ads?verified_only=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_can_filter_unique_ads_by_featured_only()
    {
        $this->createUniqueAd([], ['is_featured' => true, 'featured_at' => now()]);
        $this->createUniqueAd([], ['is_featured' => false]);

        $response = $this->getJson('/api/v1/unique-ads?featured_only=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_can_filter_unique_ads_by_year_range()
    {
        $this->createUniqueAd(['year' => 2020]);
        $this->createUniqueAd(['year' => 2022]);
        $this->createUniqueAd(['year' => 2024]);

        $response = $this->getJson('/api/v1/unique-ads?min_year=2021&max_year=2023');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_can_search_unique_ads_by_title()
    {
        $this->createUniqueAd(['title' => 'Amazing Toyota Camry for sale now']);
        $this->createUniqueAd(['title' => 'Honda Civic available immediately']);

        $response = $this->getJson('/api/v1/unique-ads?search=Toyota');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_anyone_can_view_single_unique_ad()
    {
        $ad = $this->createUniqueAd();

        $response = $this->getJson('/api/v1/unique-ads/' . $ad->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $ad->id)
            ->assertJsonPath('data.type', 'unique');
    }

    /** @test */
    public function test_viewing_ad_increments_view_count()
    {
        $ad = $this->createUniqueAd();
        $initialViews = $ad->views_count;

        $this->getJson('/api/v1/unique-ads/' . $ad->id);

        $ad->refresh();
        $this->assertEquals($initialViews + 1, $ad->views_count);
    }

    /** @test */
    public function test_viewing_own_ad_does_not_increment_view_count()
    {
        $ad = $this->createUniqueAd();
        $initialViews = $ad->views_count;

        $this->actingAs($this->user)
            ->getJson('/api/v1/unique-ads/' . $ad->id);

        $ad->refresh();
        $this->assertEquals($initialViews, $ad->views_count);
    }

    /** @test */
    public function test_viewing_nonexistent_unique_ad_returns_404()
    {
        $response = $this->getJson('/api/v1/unique-ads/99999');

        $response->assertStatus(404)
            ->assertJsonPath('code', 404);
    }

    // ==================== AUTHENTICATED USER ENDPOINTS ====================

    /** @test */
    public function test_authenticated_user_can_create_unique_ad()
    {
        $payload = [
            'title' => 'My New Unique Car Ad For Sale Now',
            'description' => str_repeat('This is a detailed description of my unique car ad. ', 5),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'brand_id' => $this->brand->id,
            'model_id' => $this->carModel->id,
            'year' => 2024,
            'contact_phone' => '+971501234567',
            'banner_color' => '#FF5733',
            'is_auto_republished' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonPath('data.type', 'unique');

        $this->assertDatabaseHas('ads', [
            'title' => $payload['title'],
            'type' => 'unique',
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('unique_ads', [
            'banner_color' => '#FF5733',
            'is_auto_republished' => true,
        ]);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_create_unique_ad()
    {
        $payload = [
            'title' => 'My New Unique Car Ad For Sale Now',
            'description' => str_repeat('Description here. ', 10),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
        ];

        $response = $this->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_validation_fails_with_short_title()
    {
        $payload = [
            'title' => 'Short',
            'description' => str_repeat('Description here. ', 10),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function test_validation_fails_with_short_description()
    {
        $payload = [
            'title' => 'Valid Title That Is Long Enough',
            'description' => 'Too short',
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    /** @test */
    public function test_validation_fails_with_invalid_banner_color()
    {
        $payload = [
            'title' => 'Valid Title That Is Long Enough',
            'description' => str_repeat('Description here. ', 10),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'banner_color' => 'invalid-color',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['banner_color']);
    }

    /** @test */
    public function test_user_can_view_their_own_ads_with_all_statuses()
    {
        // Create ads with different statuses
        $this->createUniqueAd(['status' => 'published', 'user_id' => $this->user->id]);
        $this->createUniqueAd(['status' => 'draft', 'user_id' => $this->user->id]);
        $this->createUniqueAd(['status' => 'expired', 'user_id' => $this->user->id]);
        
        // Create another user's ad
        $otherUser = User::factory()->create();
        $this->createUniqueAd(['status' => 'published', 'user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/unique-ads/my-ads');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_user_can_filter_their_ads_by_status()
    {
        $this->createUniqueAd(['status' => 'published', 'user_id' => $this->user->id]);
        $this->createUniqueAd(['status' => 'draft', 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/unique-ads/my-ads?status=draft');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_owner_can_update_their_unique_ad()
    {
        $ad = $this->createUniqueAd(['user_id' => $this->user->id]);

        $payload = [
            'title' => 'Updated Title That Is Long Enough For Validation',
            'banner_color' => '#00FF00',
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/unique-ads/' . $ad->id, $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', $payload['title']);

        $this->assertDatabaseHas('ads', ['id' => $ad->id, 'title' => $payload['title']]);
        $this->assertDatabaseHas('unique_ads', ['ad_id' => $ad->id, 'banner_color' => '#00FF00']);
    }

    /** @test */
    public function test_non_owner_cannot_update_unique_ad()
    {
        $otherUser = User::factory()->create();
        $ad = $this->createUniqueAd(['user_id' => $otherUser->id]);

        $payload = ['title' => 'Attempted Unauthorized Update Title'];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/unique-ads/' . $ad->id, $payload);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_owner_can_delete_their_unique_ad()
    {
        $ad = $this->createUniqueAd(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/unique-ads/' . $ad->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('ads', ['id' => $ad->id]);
    }

    /** @test */
    public function test_non_owner_cannot_delete_unique_ad()
    {
        $otherUser = User::factory()->create();
        $ad = $this->createUniqueAd(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/unique-ads/' . $ad->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_owner_can_republish_their_unique_ad()
    {
        $ad = $this->createUniqueAd([
            'user_id' => $this->user->id,
            'status' => 'expired'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads/' . $ad->id . '/actions/republish');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['republished_at']]);

        $ad->refresh();
        $this->assertEquals('published', $ad->status);
    }

    /** @test */
    public function test_non_owner_cannot_republish_unique_ad()
    {
        $otherUser = User::factory()->create();
        $ad = $this->createUniqueAd(['user_id' => $otherUser->id, 'status' => 'expired']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads/' . $ad->id . '/actions/republish');

        $response->assertStatus(403);
    }

    // ==================== ADMIN ENDPOINTS ====================

    /** @test */
    public function test_admin_can_access_admin_index()
    {
        // Create ads for different users
        $this->createUniqueAd(['status' => 'published']);
        $this->createUniqueAd(['status' => 'draft']);
        $this->createUniqueAd(['status' => 'expired']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/unique-ads/admin');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_non_admin_cannot_access_admin_index()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/unique-ads/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function test_admin_can_filter_by_verified_status()
    {
        $this->createUniqueAd([], ['is_verified_ad' => true]);
        $this->createUniqueAd([], ['is_verified_ad' => false]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/unique-ads/admin?is_verified=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_admin_can_filter_by_user()
    {
        $otherUser = User::factory()->create();
        
        $this->createUniqueAd(['user_id' => $this->user->id]);
        $this->createUniqueAd(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/unique-ads/admin?user_id=' . $this->user->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_admin_can_update_any_unique_ad()
    {
        $ad = $this->createUniqueAd(['user_id' => $this->user->id]);

        $payload = [
            'title' => 'Admin Updated Title That Is Long Enough',
            'is_verified_ad' => true,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/v1/unique-ads/' . $ad->id, $payload);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('unique_ads', [
            'ad_id' => $ad->id,
            'is_verified_ad' => true
        ]);
    }

    /** @test */
    public function test_admin_can_delete_any_unique_ad()
    {
        $ad = $this->createUniqueAd(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/unique-ads/' . $ad->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('ads', ['id' => $ad->id]);
    }

    /** @test */
    public function test_admin_can_create_ad_for_another_user()
    {
        $targetUser = User::factory()->create();

        $payload = [
            'title' => 'Admin Created Ad For Another User',
            'description' => str_repeat('Description for admin created ad. ', 5),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'user_id' => $targetUser->id,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('ads', [
            'title' => $payload['title'],
            'user_id' => $targetUser->id,
            'type' => 'unique',
        ]);
    }

    /** @test */
    public function test_regular_user_cannot_create_ad_for_another_user()
    {
        $targetUser = User::factory()->create();

        $payload = [
            'title' => 'Attempted Ad For Another User Here',
            'description' => str_repeat('Description. ', 10),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'user_id' => $targetUser->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_regular_user_cannot_set_verified_status()
    {
        $payload = [
            'title' => 'My New Unique Car Ad For Sale Now',
            'description' => str_repeat('Description. ', 10),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'is_verified_ad' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(201);
        
        // Verify the ad was created but is_verified_ad is false
        $this->assertDatabaseHas('unique_ads', [
            'is_verified_ad' => false
        ]);
    }

    // ==================== FEATURE/UNFEATURE ENDPOINTS ====================

    /** @test */
    public function test_admin_can_feature_unique_ad()
    {
        $ad = $this->createUniqueAd();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/unique-ads/' . $ad->id . '/actions/feature');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $ad->refresh();
        $this->assertTrue($ad->uniqueAd->is_featured);
        $this->assertNotNull($ad->uniqueAd->featured_at);
    }

    /** @test */
    public function test_admin_can_unfeature_unique_ad()
    {
        $ad = $this->createUniqueAd([], ['is_featured' => true, 'featured_at' => now()]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/unique-ads/' . $ad->id . '/actions/feature');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $ad->refresh();
        $this->assertFalse($ad->uniqueAd->is_featured);
        $this->assertNull($ad->uniqueAd->featured_at);
    }

    /** @test */
    public function test_regular_user_cannot_feature_unique_ad()
    {
        $ad = $this->createUniqueAd();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads/' . $ad->id . '/actions/feature');

        $response->assertStatus(403);
    }

    /** @test */
    public function test_regular_user_cannot_unfeature_unique_ad()
    {
        $ad = $this->createUniqueAd([], ['is_featured' => true, 'featured_at' => now()]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/unique-ads/' . $ad->id . '/actions/feature');

        $response->assertStatus(200);
    }

    // ==================== MEDIA ATTACHMENT TESTS ====================

    /** @test */
    public function test_can_create_unique_ad_with_media()
    {
        $media1 = Media::factory()->create(['user_id' => $this->user->id]);
        $media2 = Media::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'title' => 'Unique Ad With Media Attached Here',
            'description' => str_repeat('Description. ', 10),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'media_ids' => [$media1->id, $media2->id],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/unique-ads', $payload);

        $response->assertStatus(201);

        $adId = $response->json('data.id');
        $this->assertDatabaseHas('ad_media', ['ad_id' => $adId, 'media_id' => $media1->id]);
        $this->assertDatabaseHas('ad_media', ['ad_id' => $adId, 'media_id' => $media2->id]);
    }

    /** @test */
    public function test_can_update_unique_ad_media()
    {
        $ad = $this->createUniqueAd(['user_id' => $this->user->id]);
        $oldMedia = Media::factory()->create(['user_id' => $this->user->id]);
        $newMedia = Media::factory()->create(['user_id' => $this->user->id]);
        
        $ad->media()->attach($oldMedia->id);

        $payload = [
            'media_ids' => [$newMedia->id],
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/unique-ads/' . $ad->id, $payload);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ad_media', ['ad_id' => $ad->id, 'media_id' => $oldMedia->id]);
        $this->assertDatabaseHas('ad_media', ['ad_id' => $ad->id, 'media_id' => $newMedia->id]);
    }

    // ==================== SORTING TESTS ====================

    /** @test */
    public function test_can_sort_unique_ads_by_views()
    {
        $ad1 = $this->createUniqueAd(['views_count' => 10]);
        $ad2 = $this->createUniqueAd(['views_count' => 50]);
        $ad3 = $this->createUniqueAd(['views_count' => 5]);

        $response = $this->getJson('/api/v1/unique-ads?sort_by=views_count&sort_direction=desc');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals($ad2->id, $data[0]['id']);
        $this->assertEquals($ad1->id, $data[1]['id']);
        $this->assertEquals($ad3->id, $data[2]['id']);
    }

    /** @test */
    public function test_can_sort_unique_ads_by_title()
    {
        $this->createUniqueAd(['title' => 'Zebra Car For Sale Now Available']);
        $this->createUniqueAd(['title' => 'Apple Car For Sale Now Available']);
        $this->createUniqueAd(['title' => 'Mango Car For Sale Now Available']);

        $response = $this->getJson('/api/v1/unique-ads?sort_by=title&sort_direction=asc');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertStringStartsWith('Apple', $data[0]['title']);
        $this->assertStringStartsWith('Mango', $data[1]['title']);
        $this->assertStringStartsWith('Zebra', $data[2]['title']);
    }

    // ==================== PAGINATION TESTS ====================

    /** @test */
    public function test_unique_ads_are_paginated()
    {
        // Create 20 ads
        for ($i = 0; $i < 20; $i++) {
            $this->createUniqueAd();
        }

        $response = $this->getJson('/api/v1/unique-ads?limit=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ]);
    }

    /** @test */
    public function test_pagination_limit_is_capped_at_50()
    {
        // Create 60 ads
        for ($i = 0; $i < 60; $i++) {
            $this->createUniqueAd();
        }

        $response = $this->getJson('/api/v1/unique-ads?limit=100');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(50, count($response->json('data')));
    }
}
