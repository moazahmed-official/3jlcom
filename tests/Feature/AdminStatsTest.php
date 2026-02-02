<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role
        $this->adminRole = Role::factory()->create([
            'name' => 'admin',
        ]);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);

        // Create regular user
        $this->regularUser = User::factory()->create();
    }

    /** @test */
    public function admin_can_view_dashboard_stats()
    {
        // Create some test data
        User::factory()->count(5)->create();
        Ad::factory()->count(10)->create(['status' => 'published']);
        Ad::factory()->count(3)->create(['status' => 'draft']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/stats/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'total_users',
                    'total_ads',
                    'active_ads',
                    'total_views',
                    'total_contacts',
                    'ads_by_type',
                ]
            ]);
    }

    /** @test */
    public function regular_user_cannot_view_dashboard_stats()
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson('/api/v1/admin/stats/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_ad_views()
    {
        $ad = Ad::factory()->create([
            'views_count' => 150,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/stats/ads/{$ad->id}/views");

        $response->assertOk()
            ->assertJsonPath('data.ad_id', $ad->id)
            ->assertJsonPath('data.views_count', 150);
    }

    /** @test */
    public function regular_user_cannot_view_ad_views_stats()
    {
        $ad = Ad::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson("/api/v1/admin/stats/ads/{$ad->id}/views");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_ad_clicks()
    {
        $ad = Ad::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/stats/ads/{$ad->id}/clicks");

        $response->assertOk()
            ->assertJsonPath('data.ad_id', $ad->id)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['ad_id', 'clicks_count']
            ]);
    }

    /** @test */
    public function regular_user_cannot_view_ad_clicks_stats()
    {
        $ad = Ad::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson("/api/v1/admin/stats/ads/{$ad->id}/clicks");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_dealer_stats()
    {
        $dealer = User::factory()->create();

        // Create ads for dealer
        Ad::factory()->count(5)->create([
            'user_id' => $dealer->id,
            'status' => 'published',
            'views_count' => 100,
            'contact_count' => 10,
        ]);

        Ad::factory()->count(2)->create([
            'user_id' => $dealer->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/stats/dealer/{$dealer->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'dealer_id',
                    'dealer_name',
                    'dealer_email',
                    'total_ads',
                    'active_ads',
                    'total_views',
                    'total_contacts',
                    'ads_by_type',
                ]
            ])
            ->assertJsonPath('data.total_ads', 7)
            ->assertJsonPath('data.active_ads', 5);
    }

    /** @test */
    public function regular_user_cannot_view_dealer_stats()
    {
        $dealer = User::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson("/api/v1/admin/stats/dealer/{$dealer->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_user_stats()
    {
        $user = User::factory()->create();

        Ad::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'published',
            'views_count' => 50,
        ]);

        Ad::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/stats/user/{$user->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user_id',
                    'user_name',
                    'user_email',
                    'total_ads',
                    'active_ads',
                    'draft_ads',
                    'total_views',
                ]
            ])
            ->assertJsonPath('data.total_ads', 4)
            ->assertJsonPath('data.active_ads', 3)
            ->assertJsonPath('data.draft_ads', 1);
    }

    /** @test */
    public function regular_user_cannot_view_user_stats()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson("/api/v1/admin/stats/user/{$user->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_ads_count_by_type()
    {
        Ad::factory()->count(5)->create([
            'type' => 'normal',
            'status' => 'published',
        ]);

        Ad::factory()->count(2)->create([
            'type' => 'normal',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/stats/ads/normal');

        $response->assertOk()
            ->assertJsonPath('data.ad_type', 'normal')
            ->assertJsonPath('data.total_count', 7)
            ->assertJsonPath('data.active_count', 5)
            ->assertJsonPath('data.draft_count', 2);
    }

    /** @test */
    public function admin_cannot_query_invalid_ad_type()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/stats/ads/invalid_type');

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid ad type. Valid types: normal, caishha, findit, auction, unique');
    }

    /** @test */
    public function regular_user_cannot_view_ads_by_type()
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson('/api/v1/admin/stats/ads/normal');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_stats()
    {
        $response = $this->getJson('/api/v1/admin/stats/dashboard');
        $response->assertStatus(401);
    }

    /** @test */
    public function admin_stats_returns_404_for_nonexistent_ad()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/stats/ads/99999/views');

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_stats_returns_404_for_nonexistent_user()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/stats/user/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_stats_returns_404_for_nonexistent_dealer()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/stats/dealer/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function dealer_stats_shows_correct_ad_type_breakdown()
    {
        $dealer = User::factory()->create();

        Ad::factory()->count(3)->create([
            'user_id' => $dealer->id,
            'type' => 'normal',
        ]);

        Ad::factory()->count(2)->create([
            'user_id' => $dealer->id,
            'type' => 'unique',
        ]);

        Ad::factory()->create([
            'user_id' => $dealer->id,
            'type' => 'auction',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/stats/dealer/{$dealer->id}");

        $response->assertOk();
        
        $adsByType = $response->json('data.ads_by_type');
        $this->assertEquals(3, $adsByType['normal'] ?? 0);
        $this->assertEquals(2, $adsByType['unique'] ?? 0);
        $this->assertEquals(1, $adsByType['auction'] ?? 0);
    }
}
