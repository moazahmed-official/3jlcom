<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create seller user
        $this->seller = User::factory()->create();
        
        // Create another user
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function seller_can_view_dashboard_stats()
    {
        // Create some ads for the seller
        Ad::factory()->count(3)->create([
            'user_id' => $this->seller->id,
            'status' => 'published',
            'views_count' => 100,
            'contact_count' => 10,
        ]);

        Ad::factory()->count(2)->create([
            'user_id' => $this->seller->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/v1/seller/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'summary' => [
                        'total_views',
                        'total_contacts',
                        'total_clicks',
                        'active_ads_count',
                        'total_ads_count',
                        'draft_ads_count',
                    ],
                    'top_ads',
                    'date_range',
                ]
            ])
            ->assertJsonPath('data.summary.active_ads_count', 3)
            ->assertJsonPath('data.summary.draft_ads_count', 2)
            ->assertJsonPath('data.summary.total_ads_count', 5);
    }

    /** @test */
    public function seller_can_view_total_views()
    {
        Ad::factory()->count(3)->create([
            'user_id' => $this->seller->id,
            'views_count' => 50,
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/v1/seller/stats/views');

        $response->assertOk()
            ->assertJsonPath('data.total_views', 150);
    }

    /** @test */
    public function seller_can_view_specific_ad_views()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->seller->id,
            'views_count' => 75,
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson("/api/v1/seller/ads/{$ad->id}/views");

        $response->assertOk()
            ->assertJsonPath('data.views_count', 75);
    }

    /** @test */
    public function seller_cannot_view_other_sellers_ad_views()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->otherUser->id,
            'views_count' => 75,
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson("/api/v1/seller/ads/{$ad->id}/views");

        $response->assertStatus(404);
    }

    /** @test */
    public function seller_can_view_total_contacts()
    {
        Ad::factory()->count(2)->create([
            'user_id' => $this->seller->id,
            'contact_count' => 20,
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/v1/seller/stats/contacts');

        $response->assertOk()
            ->assertJsonPath('data.total_contacts', 40);
    }

    /** @test */
    public function seller_can_view_specific_ad_contacts()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->seller->id,
            'contact_count' => 30,
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson("/api/v1/seller/ads/{$ad->id}/contacts");

        $response->assertOk()
            ->assertJsonPath('data.contact_count', 30);
    }

    /** @test */
    public function seller_cannot_view_other_sellers_ad_contacts()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->otherUser->id,
            'contact_count' => 30,
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson("/api/v1/seller/ads/{$ad->id}/contacts");

        $response->assertStatus(404);
    }

    /** @test */
    public function can_increment_ad_views()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->seller->id,
            'views_count' => 10,
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->postJson("/api/v1/seller/ads/{$ad->id}/views");

        $response->assertOk()
            ->assertJsonPath('data.views_count', 11);

        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'views_count' => 11,
        ]);
    }

    /** @test */
    public function ad_owner_view_is_not_counted()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->seller->id,
            'views_count' => 10,
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/seller/ads/{$ad->id}/views");

        $response->assertOk();

        // Views should not increment for owner
        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'views_count' => 10,
        ]);
    }

    /** @test */
    public function can_increment_ad_contacts()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->seller->id,
            'contact_count' => 5,
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->postJson("/api/v1/seller/ads/{$ad->id}/contacts");

        $response->assertOk()
            ->assertJsonPath('data.contact_count', 6);

        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'contact_count' => 6,
        ]);
    }

    /** @test */
    public function seller_can_view_total_clicks()
    {
        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/v1/seller/stats/clicks');

        $response->assertOk()
            ->assertJsonStructure(['status', 'message', 'data' => ['total_clicks']]);
    }

    /** @test */
    public function seller_can_view_specific_ad_clicks()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->seller->id,
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson("/api/v1/seller/ads/{$ad->id}/clicks");

        $response->assertOk()
            ->assertJsonStructure(['status', 'message', 'data' => ['ad_id', 'click_count']]);
    }

    /** @test */
    public function guest_cannot_access_seller_stats()
    {
        $response = $this->getJson('/api/v1/seller/dashboard');
        $response->assertStatus(401);
    }

    /** @test */
    public function dashboard_shows_top_performing_ads()
    {
        // Create ads with different view counts
        Ad::factory()->create([
            'user_id' => $this->seller->id,
            'status' => 'published',
            'views_count' => 500,
            'title' => 'Top Ad',
        ]);

        Ad::factory()->create([
            'user_id' => $this->seller->id,
            'status' => 'published',
            'views_count' => 100,
            'title' => 'Second Ad',
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/v1/seller/dashboard');

        $response->assertOk();
        
        $topAds = $response->json('data.top_ads');
        $this->assertCount(2, $topAds);
        $this->assertEquals('Top Ad', $topAds[0]['title']);
        $this->assertEquals(500, $topAds[0]['views']);
    }

    /** @test */
    public function dashboard_supports_date_range_filtering()
    {
        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/v1/seller/dashboard?date_from=2026-01-01&date_to=2026-01-31');

        $response->assertOk()
            ->assertJsonPath('data.date_range.from', '2026-01-01')
            ->assertJsonPath('data.date_range.to', '2026-01-31');
    }
}
