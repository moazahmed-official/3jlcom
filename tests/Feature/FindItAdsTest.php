<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Brand;
use App\Models\FinditMatch;
use App\Models\FinditRequest;
use App\Models\NormalAd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FindItAdsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $dealer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create regular user
        $this->user = User::factory()->create([
            'account_type' => 'individual',
        ]);

        // Create dealer user
        $this->dealer = User::factory()->create([
            'account_type' => 'showroom',
        ]);
    }

    // ==========================================
    // CREATE FINDIT REQUEST TESTS
    // ==========================================

    public function test_user_can_create_findit_request(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/findit-ads', [
            'title' => 'Looking for Toyota Camry',
            'description' => 'Need a reliable family sedan',
            'min_price' => 15000,
            'max_price' => 25000,
            'min_year' => 2020,
            'max_year' => 2023,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'FindIt request created and activated successfully.',
            ])
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.title', 'Looking for Toyota Camry');

        $this->assertDatabaseHas('findit_requests', [
            'user_id' => $this->user->id,
            'title' => 'Looking for Toyota Camry',
            'status' => 'active',
        ]);
    }

    public function test_create_request_requires_title(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/findit-ads', [
            'min_price' => 15000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_request_validates_price_range(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/findit-ads', [
            'title' => 'Test Request',
            'min_price' => 50000,
            'max_price' => 25000, // min > max should fail
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_price']);
    }

    public function test_unauthenticated_user_cannot_create_request(): void
    {
        $response = $this->postJson('/api/v1/findit-ads', [
            'title' => 'Test Request',
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // LIST FINDIT REQUESTS TESTS
    // ==========================================

    public function test_user_can_list_own_requests(): void
    {
        Sanctum::actingAs($this->user);

        // Create requests for the user
        FinditRequest::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create requests for another user (should not appear)
        FinditRequest::factory()->count(2)->create([
            'user_id' => $this->dealer->id,
        ]);

        $response = $this->getJson('/api/v1/findit-ads/my-requests');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_requests_can_filter_by_status(): void
    {
        Sanctum::actingAs($this->user);

        FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/findit-ads/my-requests?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'active');
    }

    // ==========================================
    // VIEW FINDIT REQUEST TESTS
    // ==========================================

    public function test_user_can_view_own_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/findit-ads/{$request->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $request->id);
    }

    public function test_user_cannot_view_others_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->dealer->id,
        ]);

        $response = $this->getJson("/api/v1/findit-ads/{$request->id}");

        $response->assertStatus(403);
    }

    // ==========================================
    // UPDATE FINDIT REQUEST TESTS
    // ==========================================

    public function test_user_can_update_own_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->putJson("/api/v1/findit-ads/{$request->id}", [
            'title' => 'Updated Title',
            'max_price' => 30000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.max_price', '30000.00');
    }

    public function test_user_cannot_update_others_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->dealer->id,
        ]);

        $response = $this->putJson("/api/v1/findit-ads/{$request->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // DELETE FINDIT REQUEST TESTS
    // ==========================================

    public function test_user_can_delete_own_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/findit-ads/{$request->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('findit_requests', [
            'id' => $request->id,
        ]);
    }

    // ==========================================
    // ACTIVATE & CLOSE TESTS
    // ==========================================

    public function test_user_can_activate_draft_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('findit_requests', [
            'id' => $request->id,
            'status' => 'active',
        ]);
    }

    public function test_cannot_activate_non_draft_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/activate");

        $response->assertStatus(422);
    }

    public function test_user_can_close_active_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/close");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');
    }

    // ==========================================
    // STATISTICS TESTS
    // ==========================================

    public function test_user_can_view_stats(): void
    {
        Sanctum::actingAs($this->user);

        // Create some requests
        FinditRequest::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/findit-ads/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_requests',
                    'active_requests',
                    'draft_requests',
                    'closed_requests',
                    'total_matches',
                ],
            ])
            ->assertJsonPath('data.total_requests', 3)
            ->assertJsonPath('data.active_requests', 2)
            ->assertJsonPath('data.draft_requests', 1);
    }

    // ==========================================
    // MATCHES TESTS
    // ==========================================

    public function test_user_can_view_matches(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        // Create some ads and matches
        $ad = Ad::factory()->create([
            'user_id' => $this->dealer->id,
            'status' => 'published',
        ]);

        FinditMatch::factory()->create([
            'findit_request_id' => $request->id,
            'ad_id' => $ad->id,
            'match_score' => 85,
        ]);

        $response = $this->getJson("/api/v1/findit-ads/{$request->id}/matches");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_dismiss_match(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $ad = Ad::factory()->create([
            'user_id' => $this->dealer->id,
            'status' => 'published',
        ]);

        $match = FinditMatch::factory()->create([
            'findit_request_id' => $request->id,
            'ad_id' => $ad->id,
            'dismissed' => false,
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/matches/{$match->id}/dismiss");

        $response->assertStatus(200);

        $this->assertDatabaseHas('findit_matches', [
            'id' => $match->id,
            'dismissed' => true,
        ]);
    }

    public function test_user_can_refresh_matches(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/refresh-matches");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'new_matches_count',
                'total_matches_count',
            ]);
    }

    // ==========================================
    // EXTEND EXPIRATION TESTS
    // ==========================================

    public function test_user_can_extend_active_request(): void
    {
        Sanctum::actingAs($this->user);

        $originalExpiry = now()->addDays(10);
        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'expires_at' => $originalExpiry,
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/extend", [
            'days' => 15,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Request extended by 15 days.',
            ]);

        $request->refresh();
        $this->assertTrue($request->expires_at->greaterThan($originalExpiry->addDays(14)));
    }

    public function test_cannot_extend_closed_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'closed',
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/extend");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot extend a closed request. Use reactivate instead.',
            ]);
    }

    public function test_extend_validates_days_range(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/extend", [
            'days' => 100, // max is 90
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['days']);
    }

    // ==========================================
    // REACTIVATE TESTS
    // ==========================================

    public function test_user_can_reactivate_closed_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'closed',
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/reactivate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Request reactivated successfully.',
            ])
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('findit_requests', [
            'id' => $request->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_reactivate_expired_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'expires_at' => now()->subDays(5), // expired
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/reactivate", [
            'days' => 30,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $request->refresh();
        $this->assertTrue($request->expires_at->greaterThan(now()));
    }

    public function test_cannot_reactivate_active_non_expired_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(10), // not expired
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/reactivate");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Request is already active.',
            ]);
    }

    // ==========================================
    // SHOW MATCH TESTS
    // ==========================================

    public function test_user_can_view_single_match(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $ad = Ad::factory()->create([
            'user_id' => $this->dealer->id,
            'status' => 'published',
        ]);

        $match = FinditMatch::factory()->create([
            'findit_request_id' => $request->id,
            'ad_id' => $ad->id,
            'match_score' => 85,
        ]);

        $response = $this->getJson("/api/v1/findit-ads/{$request->id}/matches/{$match->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.id', $match->id)
            ->assertJsonPath('data.match_score', 85);
    }

    public function test_cannot_view_match_from_other_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $otherRequest = FinditRequest::factory()->create([
            'user_id' => $this->dealer->id,
            'status' => 'active',
        ]);

        $ad = Ad::factory()->create([
            'user_id' => $this->dealer->id,
            'status' => 'published',
        ]);

        $match = FinditMatch::factory()->create([
            'findit_request_id' => $otherRequest->id, // belongs to different request
            'ad_id' => $ad->id,
        ]);

        $response = $this->getJson("/api/v1/findit-ads/{$request->id}/matches/{$match->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Match does not belong to this request.',
            ]);
    }

    // ==========================================
    // RESTORE MATCH TESTS
    // ==========================================

    public function test_user_can_restore_dismissed_match(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $ad = Ad::factory()->create([
            'user_id' => $this->dealer->id,
            'status' => 'published',
        ]);

        $match = FinditMatch::factory()->create([
            'findit_request_id' => $request->id,
            'ad_id' => $ad->id,
            'dismissed' => true,
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/matches/{$match->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Match restored successfully.',
            ]);

        $this->assertDatabaseHas('findit_matches', [
            'id' => $match->id,
            'dismissed' => false,
        ]);
    }

    public function test_cannot_restore_non_dismissed_match(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $ad = Ad::factory()->create([
            'user_id' => $this->dealer->id,
            'status' => 'published',
        ]);

        $match = FinditMatch::factory()->create([
            'findit_request_id' => $request->id,
            'ad_id' => $ad->id,
            'dismissed' => false,
        ]);

        $response = $this->postJson("/api/v1/findit-ads/{$request->id}/matches/{$match->id}/restore");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Match is not dismissed.',
            ]);
    }

    // ==========================================
    // BULK ACTION TESTS
    // ==========================================

    public function test_admin_can_bulk_activate_requests(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $request1 = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
        $request2 = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/v1/findit-ads/actions/bulk', [
            'action' => 'activate',
            'ids' => [$request1->id, $request2->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.processed', 2);

        $this->assertDatabaseHas('findit_requests', ['id' => $request1->id, 'status' => 'active']);
        $this->assertDatabaseHas('findit_requests', ['id' => $request2->id, 'status' => 'active']);
    }

    public function test_admin_can_bulk_close_requests(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $request1 = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(10),
        ]);
        $request2 = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(10),
        ]);

        $response = $this->postJson('/api/v1/findit-ads/actions/bulk', [
            'action' => 'close',
            'ids' => [$request1->id, $request2->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.processed', 2);

        $this->assertDatabaseHas('findit_requests', ['id' => $request1->id, 'status' => 'closed']);
        $this->assertDatabaseHas('findit_requests', ['id' => $request2->id, 'status' => 'closed']);
    }

    public function test_admin_can_bulk_delete_requests(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $request1 = FinditRequest::factory()->create(['user_id' => $this->user->id]);
        $request2 = FinditRequest::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/v1/findit-ads/actions/bulk', [
            'action' => 'delete',
            'ids' => [$request1->id, $request2->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.processed', 2);

        $this->assertSoftDeleted('findit_requests', ['id' => $request1->id]);
        $this->assertSoftDeleted('findit_requests', ['id' => $request2->id]);
    }

    public function test_admin_can_bulk_extend_requests(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $request1 = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(5),
        ]);
        $request2 = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(5),
        ]);

        $response = $this->postJson('/api/v1/findit-ads/actions/bulk', [
            'action' => 'extend',
            'ids' => [$request1->id, $request2->id],
            'days' => 15,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.processed', 2);

        // Both requests should have extended expiry
        $request1->refresh();
        $request2->refresh();
        $this->assertTrue($request1->expires_at->greaterThan(now()->addDays(15)));
        $this->assertTrue($request2->expires_at->greaterThan(now()->addDays(15)));
    }

    public function test_non_admin_cannot_bulk_action(): void
    {
        Sanctum::actingAs($this->user);

        $request = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/v1/findit-ads/actions/bulk', [
            'action' => 'activate',
            'ids' => [$request->id],
        ]);

        $response->assertStatus(403);
    }

    public function test_bulk_action_handles_mixed_results(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        // One that can be activated (draft)
        $draftRequest = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        // One that cannot be activated (already active)
        $activeRequest = FinditRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(10),
        ]);

        $response = $this->postJson('/api/v1/findit-ads/actions/bulk', [
            'action' => 'activate',
            'ids' => [$draftRequest->id, $activeRequest->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.processed', 1)
            ->assertJsonPath('data.failed', 1);
    }
}
