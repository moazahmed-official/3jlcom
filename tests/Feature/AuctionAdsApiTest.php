<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Model as VehicleModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuctionAdsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected User $otherUser;
    protected Brand $brand;
    protected VehicleModel $model;
    protected Category $category;
    protected City $city;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->otherUser = User::factory()->create();

        // Create required related models
        $this->country = Country::factory()->create();
        $this->city = City::factory()->create(['country_id' => $this->country->id]);
        $this->category = Category::factory()->create();
        $this->brand = Brand::factory()->create();
        $this->model = VehicleModel::factory()->create(['brand_id' => $this->brand->id]);
    }

    // ==========================================
    // PUBLIC ENDPOINTS TESTS
    // ==========================================

    /** @test */
    public function public_can_list_published_auction_ads(): void
    {
        $ad = $this->createPublishedAuction();

        $response = $this->getJson('/api/v1/auction-ads');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'title', 'description', 'type',
                        'auction' => ['start_price', 'start_time', 'end_time', 'status']
                    ]
                ]
            ]);
    }

    /** @test */
    public function public_can_view_single_auction_ad(): void
    {
        $ad = $this->createPublishedAuction();

        $response = $this->getJson("/api/v1/auction-ads/{$ad->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $ad->id)
            ->assertJsonPath('data.type', 'auction');
    }

    /** @test */
    public function public_cannot_see_unpublished_auction_ads(): void
    {
        $ad = $this->createAuction(['status' => 'draft']);

        $response = $this->getJson('/api/v1/auction-ads');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function public_can_filter_auctions_by_status(): void
    {
        // Create active auction
        $activeAd = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ]);

        // Create ended auction
        $endedAd = $this->createPublishedAuction([
            'start_time' => now()->subDays(7),
            'end_time' => now()->subHour(),
        ]);

        $response = $this->getJson('/api/v1/auction-ads?auction_status=active');

        $response->assertOk();
        // Should only include active auction
    }

    // ==========================================
    // AUTHENTICATION TESTS
    // ==========================================

    /** @test */
    public function unauthenticated_user_cannot_create_auction(): void
    {
        $response = $this->postJson('/api/v1/auction-ads', [
            'title' => 'Test Auction',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function unauthenticated_user_cannot_place_bid(): void
    {
        $ad = $this->createPublishedAuction();

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => 1000,
        ]);

        $response->assertUnauthorized();
    }

    // ==========================================
    // CREATE AUCTION TESTS
    // ==========================================

    /** @test */
    public function authenticated_user_can_create_auction(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auction-ads', [
            'title' => 'My Test Auction',
            'description' => 'A test auction description',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'model_id' => $this->model->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'year' => 2020,
            'start_price' => 5000,
            'start_time' => now()->addHour()->toISOString(),
            'end_time' => now()->addDays(3)->toISOString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'My Test Auction')
            ->assertJsonPath('data.type', 'auction');

        $this->assertDatabaseHas('ads', [
            'title' => 'My Test Auction',
            'type' => 'auction',
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('auctions', [
            'start_price' => 5000,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function auction_requires_valid_duration(): void
    {
        Sanctum::actingAs($this->user);

        // Duration too short (less than 1 hour)
        $response = $this->postJson('/api/v1/auction-ads', [
            'title' => 'Short Auction',
            'description' => 'Test',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'model_id' => $this->model->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'year' => 2020,
            'start_price' => 5000,
            'start_time' => now()->addHour()->toISOString(),
            'end_time' => now()->addMinutes(30)->toISOString(), // Too short
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);
    }

    /** @test */
    public function reserve_price_must_be_greater_than_or_equal_to_start_price(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auction-ads', [
            'title' => 'Test Auction',
            'description' => 'Test',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'model_id' => $this->model->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'year' => 2020,
            'start_price' => 10000,
            'reserve_price' => 5000, // Less than start_price
            'start_time' => now()->addHour()->toISOString(),
            'end_time' => now()->addDays(3)->toISOString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reserve_price']);
    }

    // ==========================================
    // UPDATE AUCTION TESTS
    // ==========================================

    /** @test */
    public function owner_can_update_own_auction(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createAuction(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/v1/auction-ads/{$ad->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');
    }

    /** @test */
    public function user_cannot_update_others_auction(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createAuction(['user_id' => $this->otherUser->id]);

        $response = $this->putJson("/api/v1/auction-ads/{$ad->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function cannot_update_start_price_when_bids_exist(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createAuctionWithBids($this->user);

        $response = $this->putJson("/api/v1/auction-ads/{$ad->id}", [
            'start_price' => 1000, // Trying to change start_price
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_price']);
    }

    // ==========================================
    // DELETE AUCTION TESTS
    // ==========================================

    /** @test */
    public function owner_can_delete_auction_without_bids(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createAuction(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/auction-ads/{$ad->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('auctions', ['ad_id' => $ad->id]);
    }

    /** @test */
    public function cannot_delete_auction_with_bids(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createAuctionWithBids($this->user);

        $response = $this->deleteJson("/api/v1/auction-ads/{$ad->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('status', 'error');
    }

    // ==========================================
    // BIDDING TESTS
    // ==========================================

    /** @test */
    public function user_can_place_bid_on_active_auction(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => $ad->auction->start_price + 100,
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('bids', [
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
        ]);
    }

    /** @test */
    public function cannot_bid_on_own_auction(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => 10000,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function bid_must_meet_minimum_requirement(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
            'start_price' => 10000,
            'minimum_bid_increment' => 100,
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => 10050, // Below minimum (start_price + increment)
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    /** @test */
    public function cannot_bid_on_ended_auction(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subDays(7),
            'end_time' => now()->subHour(),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => 20000,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function cannot_bid_on_auction_not_yet_started(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(3),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => 20000,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function anti_snipe_extends_auction_when_bid_in_window(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subDays(3),
            'end_time' => now()->addMinutes(4), // Within 5 minute anti-snipe window
            'anti_snip_window_seconds' => 300,
            'anti_snip_extension_seconds' => 300,
        ], ['user_id' => $this->user->id]);

        $originalEndTime = $ad->auction->end_time->copy();

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => $ad->auction->start_price + 100,
        ]);

        $response->assertCreated()
            ->assertJsonPath('anti_snipe.triggered', true);

        $ad->refresh();
        $this->assertTrue($ad->auction->end_time->gt($originalEndTime));
    }

    // ==========================================
    // AUCTION LIFECYCLE TESTS
    // ==========================================

    /** @test */
    public function owner_can_close_ended_auction(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createAuctionWithBids($this->user, [
            'start_time' => now()->subDays(7),
            'end_time' => now()->subHour(),
        ]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/actions/close");

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $ad->refresh();
        $this->assertEquals('closed', $ad->auction->status);
    }

    /** @test */
    public function owner_cannot_close_active_auction_early(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/actions/close");

        $response->assertUnprocessable();
    }

    /** @test */
    public function admin_can_close_active_auction_early(): void
    {
        Sanctum::actingAs($this->admin);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/actions/close");

        $response->assertOk();

        $ad->refresh();
        $this->assertEquals('closed', $ad->auction->status);
    }

    /** @test */
    public function owner_can_cancel_auction_without_bids(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createPublishedAuction([], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/actions/cancel");

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $ad->refresh();
        $this->assertEquals('cancelled', $ad->auction->status);
    }

    /** @test */
    public function owner_cannot_cancel_auction_with_bids(): void
    {
        Sanctum::actingAs($this->user);

        $ad = $this->createAuctionWithBids($this->user);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/actions/cancel");

        $response->assertUnprocessable();
    }

    /** @test */
    public function admin_can_cancel_auction_with_bids(): void
    {
        Sanctum::actingAs($this->admin);

        $ad = $this->createAuctionWithBids($this->user);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/actions/cancel");

        $response->assertOk();

        $ad->refresh();
        $this->assertEquals('cancelled', $ad->auction->status);
    }

    // ==========================================
    // MY ADS / MY BIDS TESTS
    // ==========================================

    /** @test */
    public function user_can_list_own_auctions(): void
    {
        Sanctum::actingAs($this->user);

        $this->createAuction(['user_id' => $this->user->id]);
        $this->createAuction(['user_id' => $this->user->id]);
        $this->createAuction(['user_id' => $this->otherUser->id]);

        $response = $this->getJson('/api/v1/auction-ads/my-ads');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_list_own_bids(): void
    {
        Sanctum::actingAs($this->otherUser);

        // Create auction and place bids
        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
        ]);

        $response = $this->getJson('/api/v1/auction-bids/my-bids');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ==========================================
    // ADMIN TESTS
    // ==========================================

    /** @test */
    public function admin_can_access_all_auctions(): void
    {
        Sanctum::actingAs($this->admin);

        $this->createAuction(['user_id' => $this->user->id]);
        $this->createAuction(['user_id' => $this->otherUser->id]);

        $response = $this->getJson('/api/v1/auction-ads/admin');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function non_admin_cannot_access_admin_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auction-ads/admin');

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_view_global_stats(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/auction-ads/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_auctions',
                    'active_auctions',
                    'pending_auctions',
                    'closed_auctions',
                    'total_bids',
                    'bids_today',
                ]
            ]);
    }

    // ==========================================
    // RESERVE PRICE TESTS
    // ==========================================

    /** @test */
    public function auction_with_reserve_met_has_winner(): void
    {
        Sanctum::actingAs($this->admin);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subDays(7),
            'end_time' => now()->subHour(),
            'start_price' => 10000,
            'reserve_price' => 15000,
            'last_price' => 20000, // Above reserve
            'bid_count' => 1,
        ], ['user_id' => $this->user->id]);

        // Create winning bid
        $bid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => 20000,
        ]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/actions/close");

        $response->assertOk()
            ->assertJsonPath('result.reserve_met', true)
            ->assertJsonPath('result.winner_id', $this->otherUser->id);
    }

    /** @test */
    public function auction_with_reserve_not_met_has_no_winner(): void
    {
        Sanctum::actingAs($this->admin);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subDays(7),
            'end_time' => now()->subHour(),
            'start_price' => 10000,
            'reserve_price' => 25000,
            'last_price' => 20000, // Below reserve
            'bid_count' => 1,
        ], ['user_id' => $this->user->id]);

        // Create bid below reserve
        Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => 20000,
        ]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/actions/close");

        $response->assertOk()
            ->assertJsonPath('result.reserve_met', false)
            ->assertJsonPath('result.winner_id', null);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    protected function createAuction(array $adOverrides = [], array $auctionOverrides = []): Ad
    {
        $ad = Ad::create(array_merge([
            'user_id' => $this->user->id,
            'type' => 'auction',
            'title' => 'Test Auction',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'model_id' => $this->model->id,
            'city_id' => $this->city->id,
            'country_id' => $this->country->id,
            'year' => 2020,
            'status' => 'draft',
        ], $adOverrides));

        Auction::create(array_merge([
            'ad_id' => $ad->id,
            'start_price' => 10000,
            'minimum_bid_increment' => 100,
            'start_time' => now()->addHour(),
            'end_time' => now()->addDays(3),
            'status' => 'active',
            'bid_count' => 0,
        ], $auctionOverrides));

        $ad->load('auction');

        return $ad;
    }

    protected function createPublishedAuction(array $auctionOverrides = [], array $adOverrides = []): Ad
    {
        return $this->createAuction(
            array_merge(['status' => 'published', 'published_at' => now()], $adOverrides),
            $auctionOverrides
        );
    }

    protected function createAuctionWithBids(User $owner, array $auctionOverrides = []): Ad
    {
        $ad = $this->createPublishedAuction(array_merge([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
            'bid_count' => 2,
            'last_price' => 10200,
        ], $auctionOverrides), ['user_id' => $owner->id]);

        Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => 10100,
        ]);

        Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => 10200,
        ]);

        return $ad;
    }

    // ==========================================
    // BID COMMENT FIELD TESTS
    // ==========================================

    /** @test */
    public function can_place_bid_with_comment(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => $ad->auction->start_price + 100,
            'comment' => 'I really want this item!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.comment', 'I really want this item!')
            ->assertJsonPath('data.status', 'active');
    }

    /** @test */
    public function bid_comment_is_optional(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => $ad->auction->start_price + 100,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.comment', null)
            ->assertJsonPath('data.status', 'active');
    }

    /** @test */
    public function bid_comment_cannot_exceed_max_length(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $response = $this->postJson("/api/v1/auction-ads/{$ad->id}/bids", [
            'price' => $ad->auction->start_price + 100,
            'comment' => str_repeat('a', 1001), // Exceeds 1000 char limit
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    }

    // ==========================================
    // BID WITHDRAWAL TESTS
    // ==========================================

    /** @test */
    public function bidder_can_withdraw_own_bid(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        // Place two bids so we can withdraw the lower one
        $bid1 = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
            'status' => 'active',
        ]);

        Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 200,
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/auction-ads/{$ad->id}/bids/{$bid1->id}");

        $response->assertOk()
            ->assertJsonPath('data.status', 'withdrawn')
            ->assertJsonStructure(['data' => ['withdrawn_at']]);

        $bid1->refresh();
        $this->assertEquals('withdrawn', $bid1->status);
        $this->assertNotNull($bid1->withdrawn_at);
    }

    /** @test */
    public function cannot_withdraw_highest_bid(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $highestBid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
            'status' => 'active',
        ]);

        $ad->auction->update([
            'last_price' => $highestBid->price,
            'bid_count' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/auction-ads/{$ad->id}/bids/{$highestBid->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Cannot withdraw the highest bid.');
    }

    /** @test */
    public function cannot_withdraw_bid_on_ended_auction(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subDays(3),
            'end_time' => now()->subHour(), // Ended
        ], ['user_id' => $this->user->id]);

        $bid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/auction-ads/{$ad->id}/bids/{$bid->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Cannot withdraw bid after auction has ended.');
    }

    /** @test */
    public function cannot_withdraw_other_users_bid(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        // Bid by auction owner
        $ownerBid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->user->id,
            'price' => $ad->auction->start_price + 100,
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/auction-ads/{$ad->id}/bids/{$ownerBid->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function cannot_withdraw_already_withdrawn_bid(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $bid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/auction-ads/{$ad->id}/bids/{$bid->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Bid has already been withdrawn.');
    }

    // ==========================================
    // GET BID DETAILS TESTS
    // ==========================================

    /** @test */
    public function bidder_can_view_own_bid_details(): void
    {
        Sanctum::actingAs($this->otherUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $bid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
            'comment' => 'Test comment',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/auction-ads/{$ad->id}/bids/{$bid->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'price',
                    'comment',
                    'status',
                    'user',
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.id', $bid->id)
            ->assertJsonPath('data.comment', 'Test comment');
    }

    /** @test */
    public function auction_owner_can_view_any_bid_details(): void
    {
        Sanctum::actingAs($this->user); // Auction owner

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $bid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/auction-ads/{$ad->id}/bids/{$bid->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $bid->id);
    }

    /** @test */
    public function non_owner_cannot_view_others_bid_details(): void
    {
        // Create a third user who is neither owner nor bidder
        $thirdUser = User::factory()->create();
        Sanctum::actingAs($thirdUser);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $bid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/auction-ads/{$ad->id}/bids/{$bid->id}");

        $response->assertForbidden();
    }

    // ==========================================
    // LIST AUCTIONS BY USER TESTS
    // ==========================================

    /** @test */
    public function can_list_auctions_by_user(): void
    {
        Sanctum::actingAs($this->otherUser);

        // Create multiple auctions for the user
        $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id, 'title' => 'User Auction 1']);

        $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id, 'title' => 'User Auction 2']);

        // Create auction for different user
        $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->otherUser->id, 'title' => 'Other Auction']);

        $response = $this->getJson("/api/v1/users/{$this->user->id}/auction-ads");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function list_auctions_by_user_returns_only_published_for_others(): void
    {
        Sanctum::actingAs($this->otherUser);

        // Create published auction
        $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        // Create draft auction
        $this->createAuction(
            ['user_id' => $this->user->id, 'status' => 'draft'],
            ['start_time' => now()->addDay(), 'end_time' => now()->addDays(3)]
        );

        $response = $this->getJson("/api/v1/users/{$this->user->id}/auction-ads");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function owner_can_see_all_own_auctions(): void
    {
        Sanctum::actingAs($this->user);

        // Create published auction
        $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        // Create draft auction
        $this->createAuction(
            ['user_id' => $this->user->id, 'status' => 'draft'],
            ['start_time' => now()->addDay(), 'end_time' => now()->addDays(3)]
        );

        $response = $this->getJson("/api/v1/users/{$this->user->id}/auction-ads");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ==========================================
    // MODERATOR ACCESS TESTS
    // ==========================================

    /** @test */
    public function moderator_can_view_auction_bids(): void
    {
        // Create moderator user
        $moderator = User::factory()->create();
        // Assume there's a roles table and user_roles pivot
        // The User model should have hasRole method that checks this
        $moderator->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'moderator']));
        
        Sanctum::actingAs($moderator);

        $ad = $this->createAuctionWithBids($this->user, [
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/v1/auction-ads/{$ad->id}/bids");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function moderator_can_view_bid_details(): void
    {
        $moderator = User::factory()->create();
        $moderator->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'moderator']));
        
        Sanctum::actingAs($moderator);

        $ad = $this->createPublishedAuction([
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay(),
        ], ['user_id' => $this->user->id]);

        $bid = Bid::create([
            'auction_id' => $ad->auction->id,
            'user_id' => $this->otherUser->id,
            'price' => $ad->auction->start_price + 100,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/auction-ads/{$ad->id}/bids/{$bid->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $bid->id);
    }
}
