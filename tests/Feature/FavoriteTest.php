<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();
        // Assign admin role
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $this->admin->roles()->attach($adminRole->id);

        // Create a test ad
        $this->ad = Ad::factory()->create(['user_id' => $this->admin->id]);
    }

    /** @test */
    public function user_can_list_their_favorites()
    {
        Sanctum::actingAs($this->user);

        // Create favorites
        Favorite::create(['user_id' => $this->user->id, 'ad_id' => $this->ad->id]);

        $response = $this->getJson('/api/v1/favorites');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'user_id',
                            'ad_id',
                            'created_at',
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function user_can_add_ad_to_favorites()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/favorites/{$this->ad->id}");

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad added to favorites successfully',
            ]);

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $this->user->id,
            'ad_id' => $this->ad->id,
        ]);
    }

    /** @test */
    public function user_cannot_add_duplicate_favorite()
    {
        Sanctum::actingAs($this->user);

        // Add favorite first time
        Favorite::create(['user_id' => $this->user->id, 'ad_id' => $this->ad->id]);

        // Try to add again
        $response = $this->postJson("/api/v1/favorites/{$this->ad->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Ad is already in favorites',
            ]);
    }

    /** @test */
    public function user_can_remove_favorite_by_favorite_id()
    {
        Sanctum::actingAs($this->user);

        $favorite = Favorite::create([
            'user_id' => $this->user->id,
            'ad_id' => $this->ad->id,
        ]);

        $response = $this->deleteJson("/api/v1/favorites/{$favorite->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Favorite removed successfully',
            ]);

        $this->assertDatabaseMissing('user_favorites', [
            'id' => $favorite->id,
        ]);
    }

    /** @test */
    public function user_can_remove_favorite_by_ad_id()
    {
        Sanctum::actingAs($this->user);

        Favorite::create([
            'user_id' => $this->user->id,
            'ad_id' => $this->ad->id,
        ]);

        $response = $this->deleteJson("/api/v1/favorites/ad/{$this->ad->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Favorite removed successfully',
            ]);

        $this->assertDatabaseMissing('user_favorites', [
            'user_id' => $this->user->id,
            'ad_id' => $this->ad->id,
        ]);
    }

    /** @test */
    public function user_cannot_remove_other_users_favorite()
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($this->user);

        $favorite = Favorite::create([
            'user_id' => $otherUser->id,
            'ad_id' => $this->ad->id,
        ]);

        $response = $this->deleteJson("/api/v1/favorites/{$favorite->id}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized to delete this favorite',
            ]);

        $this->assertDatabaseHas('user_favorites', [
            'id' => $favorite->id,
        ]);
    }

    /** @test */
    public function user_can_check_if_ad_is_favorited()
    {
        Sanctum::actingAs($this->user);

        // Not favorited yet
        $response = $this->getJson("/api/v1/favorites/check/{$this->ad->id}");
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'is_favorited' => false,
                    'ad_id' => $this->ad->id,
                ],
            ]);

        // Add to favorites
        Favorite::create(['user_id' => $this->user->id, 'ad_id' => $this->ad->id]);

        // Check again
        $response = $this->getJson("/api/v1/favorites/check/{$this->ad->id}");
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'is_favorited' => true,
                    'ad_id' => $this->ad->id,
                ],
            ]);
    }

    /** @test */
    public function user_can_get_favorites_count()
    {
        Sanctum::actingAs($this->user);

        // No favorites initially
        $response = $this->getJson('/api/v1/favorites/count');
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'count' => 0,
                ],
            ]);

        // Add favorites
        Favorite::create(['user_id' => $this->user->id, 'ad_id' => $this->ad->id]);
        $ad2 = Ad::factory()->create(['user_id' => $this->admin->id]);
        Favorite::create(['user_id' => $this->user->id, 'ad_id' => $ad2->id]);

        // Check count
        $response = $this->getJson('/api/v1/favorites/count');
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'count' => 2,
                ],
            ]);
    }

    /** @test */
    public function user_can_toggle_favorite()
    {
        Sanctum::actingAs($this->user);

        // Toggle on (add)
        $response = $this->postJson("/api/v1/favorites/toggle/{$this->ad->id}");
        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Favorite added successfully',
                'data' => [
                    'is_favorited' => true,
                    'ad_id' => $this->ad->id,
                ],
            ]);

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $this->user->id,
            'ad_id' => $this->ad->id,
        ]);

        // Toggle off (remove)
        $response = $this->postJson("/api/v1/favorites/toggle/{$this->ad->id}");
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Favorite removed successfully',
                'data' => [
                    'is_favorited' => false,
                    'ad_id' => $this->ad->id,
                ],
            ]);

        $this->assertDatabaseMissing('user_favorites', [
            'user_id' => $this->user->id,
            'ad_id' => $this->ad->id,
        ]);
    }

    /** @test */
    public function guest_cannot_access_favorites()
    {
        $response = $this->getJson('/api/v1/favorites');
        $response->assertStatus(401);

        $response = $this->postJson("/api/v1/favorites/{$this->ad->id}");
        $response->assertStatus(401);

        $favorite = Favorite::create(['user_id' => $this->user->id, 'ad_id' => $this->ad->id]);
        $response = $this->deleteJson("/api/v1/favorites/{$favorite->id}");
        $response->assertStatus(401);
    }
}
