<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create test users
        $this->user = User::factory()->create();
        $this->user->roles()->attach($userRole->id);

        $this->otherUser = User::factory()->create();
        $this->otherUser->roles()->attach($userRole->id);
    }

    /** @test */
    public function user_can_list_their_saved_searches()
    {
        // Create saved searches for user
        SavedSearch::factory()->count(3)->create(['user_id' => $this->user->id]);
        SavedSearch::factory()->create(['user_id' => $this->otherUser->id]); // Other user's search

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/saved-searches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'page',
                    'per_page',
                    'total',
                    'items' => [
                        '*' => [
                            'id',
                            'user_id',
                            'query_params',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.total', 3);
    }

    /** @test */
    public function user_can_create_saved_search()
    {
        Sanctum::actingAs($this->user);

        $queryParams = [
            'brand_id' => 1,
            'city_id' => 2,
            'min_price' => 5000,
            'max_price' => 15000,
        ];

        $response = $this->postJson('/api/v1/saved-searches', [
            'query_params' => $queryParams,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Search saved successfully',
            ])
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.query_params', $queryParams);

        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function creating_saved_search_requires_query_params()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/saved-searches', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query_params']);
    }

    /** @test */
    public function user_can_view_their_saved_search()
    {
        $search = SavedSearch::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/saved-searches/{$search->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $search->id,
                    'user_id' => $this->user->id,
                ],
            ]);
    }

    /** @test */
    public function user_cannot_view_other_users_saved_search()
    {
        $search = SavedSearch::factory()->create(['user_id' => $this->otherUser->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/saved-searches/{$search->id}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized to access this saved search',
            ]);
    }

    /** @test */
    public function user_can_update_their_saved_search()
    {
        $search = SavedSearch::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $newQueryParams = [
            'brand_id' => 5,
            'year_min' => 2020,
        ];

        $response = $this->putJson("/api/v1/saved-searches/{$search->id}", [
            'query_params' => $newQueryParams,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Saved search updated successfully',
            ])
            ->assertJsonPath('data.query_params', $newQueryParams);

        $this->assertDatabaseHas('saved_searches', [
            'id' => $search->id,
            'query_params' => json_encode($newQueryParams),
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_saved_search()
    {
        $search = SavedSearch::factory()->create(['user_id' => $this->otherUser->id]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/saved-searches/{$search->id}", [
            'query_params' => ['brand_id' => 1],
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized to update this saved search',
            ]);
    }

    /** @test */
    public function user_can_delete_their_saved_search()
    {
        $search = SavedSearch::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/saved-searches/{$search->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Saved search deleted successfully',
            ]);

        $this->assertDatabaseMissing('saved_searches', [
            'id' => $search->id,
        ]);
    }

    /** @test */
    public function user_cannot_delete_other_users_saved_search()
    {
        $search = SavedSearch::factory()->create(['user_id' => $this->otherUser->id]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/saved-searches/{$search->id}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized to delete this saved search',
            ]);

        $this->assertDatabaseHas('saved_searches', [
            'id' => $search->id,
        ]);
    }

    /** @test */
    public function guest_cannot_access_saved_searches()
    {
        $response = $this->getJson('/api/v1/saved-searches');

        $response->assertStatus(401);
    }
}
