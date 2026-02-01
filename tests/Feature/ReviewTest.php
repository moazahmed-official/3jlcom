<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $seller;
    protected User $admin;
    protected Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seller = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->ad = Ad::factory()->create(['user_id' => $this->seller->id]);
    }

    /** @test */
    public function guest_can_list_all_reviews()
    {
        Review::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/reviews');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    'reviews' => [
                        '*' => ['id', 'title', 'body', 'stars', 'created_at']
                    ],
                    'pagination'
                ]
            ]);
    }

    /** @test */
    public function guest_can_view_single_review()
    {
        $review = Review::factory()->create();

        $response = $this->getJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.review.id', $review->id);
    }

    /** @test */
    public function guest_can_list_reviews_for_specific_ad()
    {
        $ad = Ad::factory()->create();
        Review::factory()->count(3)->for($ad, 'ad')->create();
        Review::factory()->count(2)->create(); // Other ads

        $response = $this->getJson("/api/v1/ads/{$ad->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.reviews.data');
    }

    /** @test */
    public function guest_can_list_reviews_for_specific_user()
    {
        $seller = User::factory()->create();
        Review::factory()->count(4)->create(['seller_id' => $seller->id]);
        Review::factory()->count(2)->create(); // Other sellers

        $response = $this->getJson("/api/v1/users/{$seller->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data.reviews.data');
    }

    /** @test */
    public function authenticated_user_can_create_review_for_ad()
    {
        Notification::fake();

        $reviewData = [
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'title' => 'Great product!',
            'body' => 'This is an excellent product. Highly recommended.',
            'stars' => 5,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', $reviewData);

        $response->assertStatus(201)
            ->assertJsonPath('data.review.title', 'Great product!')
            ->assertJsonPath('data.review.stars', 5);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'seller_id' => $this->seller->id,
            'ad_id' => $this->ad->id,
            'stars' => 5,
        ]);

        // Verify notification sent to seller
        Notification::assertSentTo($this->seller, ReviewReceivedNotification::class);
    }

    /** @test */
    public function authenticated_user_can_create_review_for_seller_only()
    {
        $reviewData = [
            'target_type' => 'seller',
            'target_id' => $this->seller->id,
            'title' => 'Good communication',
            'body' => 'The seller was very responsive.',
            'stars' => 4,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', $reviewData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'seller_id' => $this->seller->id,
            'ad_id' => null,
            'stars' => 4,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_review()
    {
        $reviewData = [
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'title' => 'Test',
            'body' => 'Test body',
            'stars' => 5,
        ];

        $response = $this->postJson('/api/v1/reviews', $reviewData);

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_review_their_own_ad()
    {
        $myAd = Ad::factory()->create(['user_id' => $this->user->id]);

        $reviewData = [
            'target_type' => 'ad',
            'target_id' => $myAd->id,
            'title' => 'My own ad',
            'body' => 'Testing self-review',
            'stars' => 5,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', $reviewData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ad_id']);
    }

    /** @test */
    public function user_cannot_review_themselves()
    {
        $reviewData = [
            'target_type' => 'seller',
            'target_id' => $this->user->id,
            'title' => 'Self review',
            'body' => 'Testing self-review',
            'stars' => 5,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', $reviewData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['seller_id']);
    }

    /** @test */
    public function user_cannot_create_duplicate_review_for_same_ad()
    {
        // Create first review
        Review::factory()->create([
            'user_id' => $this->user->id,
            'seller_id' => $this->seller->id,
            'ad_id' => $this->ad->id,
        ]);

        // Attempt duplicate
        $reviewData = [
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'title' => 'Another review',
            'body' => 'Duplicate attempt',
            'stars' => 3,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', $reviewData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ad_id']);
    }

    /** @test */
    public function review_creation_requires_valid_stars_rating()
    {
        $reviewData = [
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'title' => 'Test',
            'body' => 'Test body',
            'stars' => 6, // Invalid: max is 5
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', $reviewData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stars']);
    }

    /** @test */
    public function review_creation_requires_minimum_stars_rating()
    {
        $reviewData = [
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'title' => 'Test',
            'body' => 'Test body',
            'stars' => 0, // Invalid: min is 1
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', $reviewData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stars']);
    }

    /** @test */
    public function review_creation_requires_all_mandatory_fields()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_type', 'target_id', 'title', 'body', 'stars']);
    }

    /** @test */
    public function authenticated_user_can_view_their_own_reviews()
    {
        Review::factory()->count(3)->create(['user_id' => $this->user->id]);
        Review::factory()->count(2)->create(); // Other users' reviews

        $response = $this->actingAs($this->user)->getJson('/api/v1/reviews/my-reviews');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.reviews.data');
    }

    /** @test */
    public function review_owner_can_update_their_review()
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated title',
            'body' => 'Updated body content',
            'stars' => 3,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/reviews/{$review->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'title' => 'Updated title',
            'stars' => 3,
        ]);
    }

    /** @test */
    public function user_cannot_update_others_review()
    {
        $otherUser = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $otherUser->id]);

        $updateData = [
            'title' => 'Hacked title',
            'body' => 'Hacked content',
            'stars' => 1,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/v1/reviews/{$review->id}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_any_review()
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Admin updated title',
            'body' => 'Admin moderated content',
            'stars' => 4,
        ];

        $response = $this->actingAs($this->admin)->putJson("/api/v1/reviews/{$review->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'title' => 'Admin updated title',
        ]);
    }

    /** @test */
    public function review_owner_can_delete_their_review()
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /** @test */
    public function user_cannot_delete_others_review()
    {
        $otherUser = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_any_review()
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /** @test */
    public function creating_review_updates_ad_rating_cache()
    {
        $ad = Ad::factory()->create();

        // Create 3 reviews with different ratings
        $this->actingAs($this->user)->postJson('/api/v1/reviews', [
            'target_type' => 'ad',
            'target_id' => $ad->id,
            'title' => 'Review 1',
            'body' => 'Body 1',
            'stars' => 5,
        ]);

        $user2 = User::factory()->create();
        $this->actingAs($user2)->postJson('/api/v1/reviews', [
            'target_type' => 'ad',
            'target_id' => $ad->id,
            'title' => 'Review 2',
            'body' => 'Body 2',
            'stars' => 3,
        ]);

        $user3 = User::factory()->create();
        $this->actingAs($user3)->postJson('/api/v1/reviews', [
            'target_type' => 'ad',
            'target_id' => $ad->id,
            'title' => 'Review 3',
            'body' => 'Body 3',
            'stars' => 4,
        ]);

        // Average should be (5+3+4)/3 = 4.00
        $ad->refresh();
        $this->assertEquals(4.00, $ad->avg_rating);
        $this->assertEquals(3, $ad->reviews_count);
    }

    /** @test */
    public function updating_review_updates_ad_rating_cache()
    {
        $review = Review::factory()->create([
            'ad_id' => $this->ad->id,
            'stars' => 5,
        ]);

        $this->ad->refresh();
        $this->assertEquals(5.00, $this->ad->avg_rating);

        // Update to 3 stars
        $this->actingAs($review->user)->putJson("/api/v1/reviews/{$review->id}", [
            'stars' => 3,
        ]);

        $this->ad->refresh();
        $this->assertEquals(3.00, $this->ad->avg_rating);
    }

    /** @test */
    public function deleting_review_updates_ad_rating_cache()
    {
        $review1 = Review::factory()->create(['ad_id' => $this->ad->id, 'stars' => 5]);
        $review2 = Review::factory()->create(['ad_id' => $this->ad->id, 'stars' => 3]);

        $this->ad->refresh();
        $this->assertEquals(4.00, $this->ad->avg_rating);
        $this->assertEquals(2, $this->ad->reviews_count);

        // Delete one review
        $this->actingAs($review1->user)->deleteJson("/api/v1/reviews/{$review1->id}");

        $this->ad->refresh();
        $this->assertEquals(3.00, $this->ad->avg_rating);
        $this->assertEquals(1, $this->ad->reviews_count);
    }

    /** @test */
    public function rate_limiting_prevents_spam_reviews()
    {
        // Make 10 successful review creations
        for ($i = 1; $i <= 10; $i++) {
            $ad = Ad::factory()->create();
            $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', [
                'target_type' => 'ad',
                'target_id' => $ad->id,
                'title' => "Review $i",
                'body' => "Body $i",
                'stars' => 4,
            ]);
            $response->assertStatus(201);
        }

        // 11th attempt should be rate limited
        $ad = Ad::factory()->create();
        $response = $this->actingAs($this->user)->postJson('/api/v1/reviews', [
            'target_type' => 'ad',
            'target_id' => $ad->id,
            'title' => 'Review 11',
            'body' => 'Should fail',
            'stars' => 4,
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Too many review submissions. Please try again later.');
    }

    /** @test */
    public function reviews_can_be_filtered_by_minimum_stars()
    {
        Review::factory()->create(['stars' => 5]);
        Review::factory()->create(['stars' => 4]);
        Review::factory()->create(['stars' => 2]);

        $response = $this->getJson('/api/v1/reviews?min_stars=4');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.reviews.data');
    }

    /** @test */
    public function review_list_respects_pagination_limits()
    {
        Review::factory()->count(60)->create();

        // Default limit should be 15
        $response = $this->getJson('/api/v1/reviews');
        $response->assertStatus(200)
            ->assertJsonCount(15, 'data.reviews.data');

        // Custom limit
        $response = $this->getJson('/api/v1/reviews?limit=25');
        $response->assertJsonCount(25, 'data.reviews.data');

        // Max limit should be 50
        $response = $this->getJson('/api/v1/reviews?limit=100');
        $response->assertJsonCount(50, 'data.reviews.data');
    }
}
