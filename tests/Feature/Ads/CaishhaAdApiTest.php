<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CaishhaAd;
use App\Models\CaishhaOffer;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaishhaAdApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $dealerUser;
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

        // Create brand and model
        $this->brand = Brand::factory()->create();
        $this->carModel = CarModel::factory()->create(['brand_id' => $this->brand->id]);

        // Create regular user (seller)
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'is_verified' => true,
            'account_type' => 'individual',
        ]);

        // Create dealer user
        $dealerRole = Role::firstOrCreate(['name' => 'dealer']);
        $this->dealerUser = User::factory()->create([
            'email_verified_at' => now(),
            'is_verified' => true,
            'account_type' => 'dealer',
        ]);
        $this->dealerUser->roles()->attach($dealerRole);

        // Create admin user
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
            'is_verified' => true,
        ]);
        $this->adminUser->roles()->attach($adminRole);
    }

    /**
     * Create a Caishha ad with all related records
     */
    protected function createCaishhaAd(array $adOverrides = [], array $caishhaAdOverrides = []): Ad
    {
        $ad = Ad::create(array_merge([
            'user_id' => $this->user->id,
            'type' => 'caishha',
            'title' => 'Test Caishha Ad Title That Is Long Enough',
            'description' => str_repeat('This is a test description for the Caishha ad. ', 5),
            'category_id' => $this->category->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'brand_id' => $this->brand?->id,
            'model_id' => $this->carModel?->id,
            'year' => 2024,
            'status' => 'published',
            'published_at' => now(),
            'contact_phone' => '+971501234567',
            'whatsapp_number' => '+971501234567',
            'views_count' => 0,
            'period_days' => 30,
        ], $adOverrides));

        CaishhaAd::create(array_merge([
            'ad_id' => $ad->id,
            'offers_window_period' => CaishhaAd::DEFAULT_DEALER_WINDOW_SECONDS,
            'offers_count' => 0,
            'sellers_visibility_period' => CaishhaAd::DEFAULT_VISIBILITY_PERIOD_SECONDS,
        ], $caishhaAdOverrides));

        return $ad->fresh(['caishhaAd']);
    }

    // ==================== PUBLIC ENDPOINTS ====================

    /** @test */
    public function test_anyone_can_list_published_caishha_ads()
    {
        $this->createCaishhaAd(['status' => 'published']);
        $this->createCaishhaAd(['status' => 'published']);
        $this->createCaishhaAd(['status' => 'draft']); // Should not appear

        $response = $this->getJson('/api/v1/caishha-ads');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_can_filter_caishha_ads_by_brand()
    {
        $otherBrand = Brand::factory()->create();
        
        $this->createCaishhaAd(['brand_id' => $this->brand->id]);
        $this->createCaishhaAd(['brand_id' => $otherBrand->id]);

        $response = $this->getJson('/api/v1/caishha-ads?brand_id=' . $this->brand->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_can_filter_caishha_ads_by_city()
    {
        $otherCity = City::factory()->create(['country_id' => $this->country->id]);
        
        $this->createCaishhaAd(['city_id' => $this->city->id]);
        $this->createCaishhaAd(['city_id' => $otherCity->id]);

        $response = $this->getJson('/api/v1/caishha-ads?city_id=' . $this->city->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function test_anyone_can_view_single_caishha_ad()
    {
        $ad = $this->createCaishhaAd();

        $response = $this->getJson('/api/v1/caishha-ads/' . $ad->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $ad->id)
            ->assertJsonPath('data.type', 'caishha');
    }

    /** @test */
    public function test_view_count_increments_for_non_owner()
    {
        $ad = $this->createCaishhaAd();
        $initialViews = $ad->views_count;

        $this->getJson('/api/v1/caishha-ads/' . $ad->id);

        $ad->refresh();
        $this->assertEquals($initialViews + 1, $ad->views_count);
    }

    // ==================== AUTHENTICATED CRUD ENDPOINTS ====================

    /** @test */
    public function test_authenticated_user_can_create_caishha_ad()
    {
        $data = [
            'title' => 'My New Caishha Ad Title Here',
            'description' => 'This is a detailed description of my vehicle for sale via Caishha auction system.',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'model_id' => $this->carModel->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'year' => 2023,
            'offers_window_period' => 43200, // 12 hours
            'sellers_visibility_period' => 86400, // 24 hours
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/caishha-ads', $data);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', $data['title'])
            ->assertJsonPath('data.type', 'caishha');
    }

    /** @test */
    public function test_unauthenticated_user_cannot_create_caishha_ad()
    {
        $data = [
            'title' => 'My New Caishha Ad',
            'description' => 'This is a detailed description.',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'model_id' => $this->carModel->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'year' => 2023,
        ];

        $response = $this->postJson('/api/v1/caishha-ads', $data);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_owner_can_update_caishha_ad()
    {
        $ad = $this->createCaishhaAd();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/caishha-ads/' . $ad->id, [
                'title' => 'Updated Caishha Ad Title Here',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Caishha Ad Title Here');
    }

    /** @test */
    public function test_non_owner_cannot_update_caishha_ad()
    {
        $ad = $this->createCaishhaAd();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson('/api/v1/caishha-ads/' . $ad->id, [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_admin_can_update_any_caishha_ad()
    {
        $ad = $this->createCaishhaAd();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson('/api/v1/caishha-ads/' . $ad->id, [
                'title' => 'Admin Updated Title Here',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Admin Updated Title Here');
    }

    /** @test */
    public function test_owner_can_delete_caishha_ad()
    {
        $ad = $this->createCaishhaAd();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/caishha-ads/' . $ad->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
        
        $this->assertDatabaseMissing('ads', ['id' => $ad->id]);
    }

    // ==================== LIFECYCLE ACTIONS ====================

    /** @test */
    public function test_owner_can_publish_caishha_ad()
    {
        $ad = $this->createCaishhaAd(['status' => 'draft']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/actions/publish');

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'published');
    }

    /** @test */
    public function test_owner_can_unpublish_caishha_ad()
    {
        $ad = $this->createCaishhaAd(['status' => 'published']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/actions/unpublish');

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'draft');
    }

    /** @test */
    public function test_owner_can_archive_caishha_ad()
    {
        $ad = $this->createCaishhaAd(['status' => 'published']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/actions/archive');

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'removed');
    }

    // ==================== MY ADS / ADMIN INDEX ====================

    /** @test */
    public function test_user_can_list_their_own_caishha_ads()
    {
        $this->createCaishhaAd(['user_id' => $this->user->id]);
        $this->createCaishhaAd(['user_id' => $this->user->id, 'status' => 'draft']);
        
        // Other user's ad
        $otherUser = User::factory()->create();
        $this->createCaishhaAd(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/caishha-ads/my-ads');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function test_admin_can_list_all_caishha_ads()
    {
        $this->createCaishhaAd(['user_id' => $this->user->id]);
        $this->createCaishhaAd(['user_id' => $this->user->id, 'status' => 'draft']);
        $otherUser = User::factory()->create();
        $this->createCaishhaAd(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/caishha-ads/admin');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_non_admin_cannot_access_admin_index()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/caishha-ads/admin');

        $response->assertStatus(403);
    }

    // ==================== OFFERS MANAGEMENT ====================

    /** @test */
    public function test_dealer_can_submit_offer_during_dealer_window()
    {
        // Create ad that was published 1 hour ago (still in dealer window)
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHour(),
        ], [
            'offers_window_period' => 129600, // 36 hours
        ]);

        $response = $this->actingAs($this->dealerUser, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/offers', [
                'price' => 25000,
                'comment' => 'I am interested in this vehicle',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.price', 25000.00);
    }

    /** @test */
    public function test_individual_cannot_submit_offer_during_dealer_window()
    {
        // Create ad that was published 1 hour ago (still in dealer window)
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHour(),
            'user_id' => User::factory()->create()->id, // Different owner
        ], [
            'offers_window_period' => 129600, // 36 hours
        ]);

        $individualUser = User::factory()->create([
            'account_type' => 'individual',
        ]);

        $response = $this->actingAs($individualUser, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/offers', [
                'price' => 25000,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_individual_can_submit_offer_after_dealer_window()
    {
        // Create ad that was published 40 hours ago (after 36-hour dealer window)
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHours(40),
            'user_id' => User::factory()->create()->id, // Different owner
        ], [
            'offers_window_period' => 129600, // 36 hours
        ]);

        $individualUser = User::factory()->create([
            'account_type' => 'individual',
        ]);

        $response = $this->actingAs($individualUser, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/offers', [
                'price' => 25000,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_owner_cannot_submit_offer_on_own_ad()
    {
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHours(40),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/offers', [
                'price' => 25000,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_user_cannot_submit_duplicate_offer()
    {
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHours(40),
            'user_id' => User::factory()->create()->id,
        ], [
            'offers_window_period' => 129600,
        ]);

        // First offer
        CaishhaOffer::create([
            'ad_id' => $ad->id,
            'user_id' => $this->dealerUser->id,
            'price' => 20000,
            'status' => 'pending',
        ]);

        // Try to submit another offer
        $response = $this->actingAs($this->dealerUser, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/offers', [
                'price' => 25000,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_owner_can_list_offers_after_visibility_period()
    {
        // Create ad that was published 40 hours ago (visibility period passed)
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHours(40),
        ], [
            'sellers_visibility_period' => 129600, // 36 hours
        ]);

        // Add some offers
        CaishhaOffer::create([
            'ad_id' => $ad->id,
            'user_id' => $this->dealerUser->id,
            'price' => 20000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/caishha-ads/' . $ad->id . '/offers');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    /** @test */
    public function test_owner_cannot_list_offers_before_visibility_period()
    {
        // Create ad that was published 1 hour ago (visibility period not passed)
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHour(),
        ], [
            'sellers_visibility_period' => 129600, // 36 hours
        ]);

        // Add offer
        CaishhaOffer::create([
            'ad_id' => $ad->id,
            'user_id' => $this->dealerUser->id,
            'price' => 20000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/caishha-ads/' . $ad->id . '/offers');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Offers not yet visible');
    }

    /** @test */
    public function test_admin_can_list_offers_anytime()
    {
        // Create ad that was published 1 hour ago (visibility period not passed)
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHour(),
        ], [
            'sellers_visibility_period' => 129600, // 36 hours
        ]);

        // Add offer
        CaishhaOffer::create([
            'ad_id' => $ad->id,
            'user_id' => $this->dealerUser->id,
            'price' => 20000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/caishha-ads/' . $ad->id . '/offers');

        $response->assertStatus(200);
    }

    /** @test */
    public function test_owner_can_accept_offer_after_visibility_period()
    {
        // Create ad that was published 40 hours ago
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHours(40),
        ], [
            'sellers_visibility_period' => 129600,
        ]);

        $offer = CaishhaOffer::create([
            'ad_id' => $ad->id,
            'user_id' => $this->dealerUser->id,
            'price' => 20000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/caishha-ads/{$ad->id}/offers/{$offer->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');
    }

    /** @test */
    public function test_owner_can_reject_offer()
    {
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHours(40),
        ], [
            'sellers_visibility_period' => 129600,
        ]);

        $offer = CaishhaOffer::create([
            'ad_id' => $ad->id,
            'user_id' => $this->dealerUser->id,
            'price' => 20000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/caishha-ads/{$ad->id}/offers/{$offer->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');
    }

    /** @test */
    public function test_accepting_offer_rejects_other_pending_offers()
    {
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHours(40),
        ], [
            'sellers_visibility_period' => 129600,
        ]);

        $offer1 = CaishhaOffer::create([
            'ad_id' => $ad->id,
            'user_id' => $this->dealerUser->id,
            'price' => 20000,
            'status' => 'pending',
        ]);

        $otherDealer = User::factory()->create();
        $offer2 = CaishhaOffer::create([
            'ad_id' => $ad->id,
            'user_id' => $otherDealer->id,
            'price' => 22000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/caishha-ads/{$ad->id}/offers/{$offer1->id}/accept");

        $offer2->refresh();
        $this->assertEquals('rejected', $offer2->status);
    }

    /** @test */
    public function test_user_can_view_their_submitted_offers()
    {
        // Create multiple Caishha ads by different users
        $adOwner = User::factory()->create();
        $ad1 = $this->createCaishhaAd(['user_id' => $adOwner->id, 'published_at' => now()->subHours(40)]);
        $ad2 = $this->createCaishhaAd(['user_id' => $adOwner->id, 'published_at' => now()->subHours(40)]);

        // Dealer submits offers
        CaishhaOffer::create([
            'ad_id' => $ad1->id,
            'user_id' => $this->dealerUser->id,
            'price' => 20000,
            'status' => 'pending',
        ]);

        CaishhaOffer::create([
            'ad_id' => $ad2->id,
            'user_id' => $this->dealerUser->id,
            'price' => 25000,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($this->dealerUser, 'sanctum')
            ->getJson('/api/v1/caishha-offers/my-offers');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data');
    }

    // ==================== BULK ACTIONS ====================

    /** @test */
    public function test_admin_can_perform_bulk_publish()
    {
        $ad1 = $this->createCaishhaAd(['status' => 'draft']);
        $ad2 = $this->createCaishhaAd(['status' => 'draft']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/caishha-ads/actions/bulk', [
                'action' => 'publish',
                'ad_ids' => [$ad1->id, $ad2->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.affected_count', 2);

        $ad1->refresh();
        $ad2->refresh();
        $this->assertEquals('published', $ad1->status);
        $this->assertEquals('published', $ad2->status);
    }

    /** @test */
    public function test_non_admin_cannot_perform_bulk_actions()
    {
        $ad1 = $this->createCaishhaAd(['status' => 'draft']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/caishha-ads/actions/bulk', [
                'action' => 'publish',
                'ad_ids' => [$ad1->id],
            ]);

        $response->assertStatus(403);
    }

    // ==================== GLOBAL STATS ====================

    /** @test */
    public function test_admin_can_view_global_stats()
    {
        $this->createCaishhaAd(['status' => 'published']);
        $this->createCaishhaAd(['status' => 'draft']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/caishha-ads/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_ads', 2)
            ->assertJsonPath('data.published_ads', 1)
            ->assertJsonPath('data.draft_ads', 1);
    }

    /** @test */
    public function test_non_admin_cannot_view_global_stats()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/caishha-ads/stats');

        $response->assertStatus(403);
    }

    // ==================== VALIDATION TESTS ====================

    /** @test */
    public function test_create_caishha_ad_validation_errors()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/caishha-ads', [
                'title' => 'Hi', // Too short
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'category_id', 'brand_id', 'model_id', 'city_id', 'country_id', 'year']);
    }

    /** @test */
    public function test_submit_offer_validation_errors()
    {
        $adOwner = User::factory()->create();
        $ad = $this->createCaishhaAd([
            'user_id' => $adOwner->id,
            'published_at' => now()->subHours(40),
        ]);

        $response = $this->actingAs($this->dealerUser, 'sanctum')
            ->postJson('/api/v1/caishha-ads/' . $ad->id . '/offers', [
                'price' => -100, // Invalid price
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    // ==================== WINDOW STATUS IN RESPONSE ====================

    /** @test */
    public function test_caishha_ad_response_includes_window_status()
    {
        $ad = $this->createCaishhaAd([
            'published_at' => now()->subHour(),
        ]);

        $response = $this->getJson('/api/v1/caishha-ads/' . $ad->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'window_status' => [
                        'is_in_dealer_window',
                        'is_in_individual_window',
                        'are_offers_visible_to_seller',
                        'can_accept_offers',
                        'dealer_window_ends_at',
                        'visibility_period_ends_at',
                    ],
                ],
            ]);
    }
}
