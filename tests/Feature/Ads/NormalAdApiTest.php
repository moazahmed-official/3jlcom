<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Media;
use App\Models\NormalAd;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NormalAdApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_authenticated_user_can_create_normal_ad(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $brand = Brand::factory()->create();
        $model = CarModel::factory()->create(['brand_id' => $brand->id]);
        
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/normal-ads', [
            'title' => 'Test Car Ad',
            'description' => 'This is a test car advertisement with good details.',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'year' => 2020,
            'price_cash' => 15000,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'type',
                    'title',
                    'description',
                    'status',
                    'user_id',
                    'category_id',
                    'city_id',
                    'country_id',
                    'brand_id',
                    'model_id',
                    'year',
                    'price_cash',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad created successfully'
            ]);

        $this->assertDatabaseHas('ads', [
            'user_id' => $user->id,
            'type' => 'normal',
            'title' => 'Test Car Ad',
            'status' => 'published'
        ]);

        $this->assertDatabaseHas('normal_ads', [
            'price_cash' => 15000
        ]);
    }

    public function test_create_normal_ad_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/normal-ads', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'description', 
                'category_id',
                'city_id',
                'country_id'
            ]);
    }

    public function test_create_normal_ad_validates_title_length(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/normal-ads', [
            'title' => 'Hi', // Too short
            'description' => 'This is a test description with enough length.',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_create_normal_ad(): void
    {
        $response = $this->postJson('/api/v1/normal-ads', [
            'title' => 'Test Ad',
            'description' => 'Test description',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_normal_ads(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        // Create some published normal ads
        $ads = Ad::factory(3)->create([
            'type' => 'normal',
            'status' => 'published',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);

        foreach ($ads as $ad) {
            NormalAd::factory()->create(['ad_id' => $ad->id]);
        }

        $response = $this->getJson('/api/v1/normal-ads');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'description',
                        'status',
                        'created_at'
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    public function test_user_can_filter_normal_ads_by_brand(): void
    {
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $brand1 = Brand::factory()->create();
        $brand2 = Brand::factory()->create();

        // Create ads with different brands
        $ad1 = Ad::factory()->create([
            'type' => 'normal',
            'status' => 'published',
            'brand_id' => $brand1->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        $ad2 = Ad::factory()->create([
            'type' => 'normal',
            'status' => 'published',
            'brand_id' => $brand2->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);

        NormalAd::factory()->create(['ad_id' => $ad1->id]);
        NormalAd::factory()->create(['ad_id' => $ad2->id]);

        $response = $this->getJson("/api/v1/normal-ads?brand_id={$brand1->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($brand1->id, $data[0]['brand_id']);
    }

    public function test_user_can_view_normal_ad_details(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'type' => 'normal',
            'status' => 'published',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        $response = $this->getJson("/api/v1/normal-ads/{$ad->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'type',
                    'title',
                    'description',
                    'status',
                    'user_id',
                    'created_at'
                ]
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $ad->id,
                    'type' => 'normal'
                ]
            ]);
    }

    public function test_viewing_ad_increments_view_count(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'type' => 'normal',
            'status' => 'published',
            'user_id' => $owner->id,
            'views_count' => 0,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        // View as different user
        $this->actingAs($viewer, 'sanctum');
        $this->getJson("/api/v1/normal-ads/{$ad->id}");

        $this->assertEquals(1, $ad->fresh()->views_count);
    }

    public function test_owner_can_update_normal_ad(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $brand = Brand::factory()->create();
        
        $ad = Ad::factory()->create([
            'type' => 'normal',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/v1/normal-ads/{$ad->id}", [
            'title' => 'Updated Ad Title',
            'description' => 'Updated description with more details about the car.',
            'brand_id' => $brand->id,
            'price_cash' => 20000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad updated successfully'
            ]);

        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'title' => 'Updated Ad Title',
            'brand_id' => $brand->id
        ]);

        $this->assertDatabaseHas('normal_ads', [
            'ad_id' => $ad->id,
            'price_cash' => 20000
        ]);
    }

    public function test_non_owner_cannot_update_normal_ad(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'type' => 'normal',
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($otherUser, 'sanctum');

        $response = $this->putJson("/api/v1/normal-ads/{$ad->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_normal_ad(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'type' => 'normal',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        $normalAd = NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/v1/normal-ads/{$ad->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad deleted successfully'
            ]);

        $this->assertSoftDeleted('ads', ['id' => $ad->id]);
        $this->assertDatabaseMissing('normal_ads', ['id' => $normalAd->id]);
    }

    public function test_owner_can_republish_normal_ad(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'type' => 'normal',
            'user_id' => $user->id,
            'status' => 'expired',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/v1/normal-ads/{$ad->id}/actions/republish");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad republished successfully'
            ]);

        $this->assertEquals('published', $ad->fresh()->status);
    }

    public function test_create_normal_ad_with_media(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        // Create media belonging to user
        $media1 = Media::factory()->create(['user_id' => $user->id]);
        $media2 = Media::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/normal-ads', [
            'title' => 'Test Car with Media',
            'description' => 'This car has beautiful photos attached.',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
            'media_ids' => [$media1->id, $media2->id],
        ]);

        $response->assertStatus(201);

        $ad = Ad::latest()->first();
        $this->assertCount(2, $ad->media);
        
        // Check media was updated with ad reference
        $this->assertDatabaseHas('media', [
            'id' => $media1->id,
            'related_resource' => 'ads',
            'related_id' => $ad->id
        ]);
    }

    public function test_non_existent_ad_returns_404(): void
    {
        $response = $this->getJson('/api/v1/normal-ads/99999');
        
        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'code' => 404,
                'message' => 'Ad not found'
            ]);
    }

    public function test_admin_can_create_ad_for_other_user(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first() ?? Role::factory()->create(['name' => 'admin']));
        
        $targetUser = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson('/api/v1/normal-ads', [
            'user_id' => $targetUser->id,
            'title' => 'Admin Created Car Ad',
            'description' => 'This ad was created by admin for another user.',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
            'price_cash' => 25000,
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('ads', [
            'user_id' => $targetUser->id,
            'title' => 'Admin Created Car Ad'
        ]);
    }

    public function test_regular_user_cannot_create_ad_for_other_user(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/normal-ads', [
            'user_id' => $targetUser->id,
            'title' => 'Unauthorized Ad Creation',
            'description' => 'This should fail.',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_admin_can_update_any_ad(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first() ?? Role::factory()->create(['name' => 'admin']));
        
        $adOwner = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'user_id' => $adOwner->id,
            'type' => 'normal',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->putJson("/api/v1/normal-ads/{$ad->id}", [
            'title' => 'Admin Updated Title',
            'price_cash' => 35000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad updated successfully'
            ]);

        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'title' => 'Admin Updated Title'
        ]);
    }

    public function test_admin_can_delete_any_ad(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first() ?? Role::factory()->create(['name' => 'admin']));
        
        $adOwner = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'user_id' => $adOwner->id,
            'type' => 'normal',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        $normalAd = NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->deleteJson("/api/v1/normal-ads/{$ad->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad deleted successfully'
            ]);

        $this->assertSoftDeleted('ads', ['id' => $ad->id]);
        $this->assertDatabaseMissing('normal_ads', ['id' => $normalAd->id]);
    }

    public function test_admin_can_republish_any_ad(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first() ?? Role::factory()->create(['name' => 'admin']));
        
        $adOwner = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'user_id' => $adOwner->id,
            'type' => 'normal',
            'status' => 'expired',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/v1/normal-ads/{$ad->id}/actions/republish");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad republished successfully'
            ]);

        $this->assertEquals('published', $ad->fresh()->status);
    }

    public function test_user_can_update_ad_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'type' => 'normal',
            'user_id' => $user->id,
            'status' => 'published',
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($user, 'sanctum');

        // Update status to draft
        $response = $this->putJson("/api/v1/normal-ads/{$ad->id}", [
            'status' => 'draft'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ad updated successfully'
            ]);

        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'status' => 'draft'
        ]);
    }

    public function test_user_cannot_set_invalid_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        
        $ad = Ad::factory()->create([
            'type' => 'normal',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
        
        NormalAd::factory()->create(['ad_id' => $ad->id]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/v1/normal-ads/{$ad->id}", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_user_can_list_own_ads(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        // Create ads for authenticated user with different statuses
        $publishedAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $user->id,
                'status' => 'published',
                'title' => 'Published Ad'
            ])
        )->create();
        
        $draftAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $user->id,
                'status' => 'draft',
                'title' => 'Draft Ad'
            ])
        )->create();
        
        // Create ad for other user (should not appear in results)
        $otherAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $otherUser->id,
                'status' => 'published',
                'title' => 'Other User Ad'
            ])
        )->create();

        $this->actingAs($user, 'sanctum');
        
        $response = $this->getJson('/api/v1/normal-ads/my-ads');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Published Ad'])
            ->assertJsonFragment(['title' => 'Draft Ad'])
            ->assertJsonMissing(['title' => 'Other User Ad']);
    }

    public function test_user_can_filter_own_ads_by_status(): void
    {
        $user = User::factory()->create();
        
        // Create ads with different statuses
        $publishedAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $user->id,
                'status' => 'published'
            ])
        )->create();
        
        $draftAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $user->id,
                'status' => 'draft'
            ])
        )->create();

        $this->actingAs($user, 'sanctum');
        
        // Filter by published status
        $response = $this->getJson('/api/v1/normal-ads/my-ads?status=published');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['status' => 'published']);
        
        // Filter by draft status
        $response = $this->getJson('/api/v1/normal-ads/my-ads?status=draft');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['status' => 'draft']);
    }

    public function test_admin_can_list_all_ads(): void
    {
        $admin = User::factory()->create();
        
        // Create admin role and attach to user
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin->roles()->attach($adminRole);
        
        $regularUser = User::factory()->create();
        
        // Create ads for different users with different statuses
        $adminAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $admin->id,
                'status' => 'published',
                'title' => 'Admin Ad'
            ])
        )->create();
        
        $userDraftAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $regularUser->id,
                'status' => 'draft',
                'title' => 'User Draft Ad'
            ])
        )->create();
        
        $userPublishedAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $regularUser->id,
                'status' => 'published',
                'title' => 'User Published Ad'
            ])
        )->create();

        $this->actingAs($admin, 'sanctum');
        
        $response = $this->getJson('/api/v1/normal-ads/admin');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['title' => 'Admin Ad'])
            ->assertJsonFragment(['title' => 'User Draft Ad'])
            ->assertJsonFragment(['title' => 'User Published Ad']);
    }

    public function test_admin_can_filter_all_ads_by_status(): void
    {
        $admin = User::factory()->create();
        
        // Create admin role and attach to user
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin->roles()->attach($adminRole);
        
        $regularUser = User::factory()->create();
        
        // Create ads with different statuses
        $draftAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $regularUser->id,
                'status' => 'draft'
            ])
        )->create();
        
        $publishedAd = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $regularUser->id,
                'status' => 'published'
            ])
        )->create();

        $this->actingAs($admin, 'sanctum');
        
        // Filter by draft status
        $response = $this->getJson('/api/v1/normal-ads/admin?status=draft');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['status' => 'draft']);
        
        // Filter by published status
        $response = $this->getJson('/api/v1/normal-ads/admin?status=published');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['status' => 'published']);
    }

    public function test_admin_can_filter_ads_by_user(): void
    {
        $admin = User::factory()->create();
        
        // Create admin role and attach to user
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin->roles()->attach($adminRole);
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create ads for different users
        $user1Ad = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $user1->id,
                'status' => 'published'
            ])
        )->create();
        
        $user2Ad = NormalAd::factory()->for(
            Ad::factory()->create([
                'user_id' => $user2->id,
                'status' => 'published'
            ])
        )->create();

        $this->actingAs($admin, 'sanctum');
        
        // Filter by user1
        $response = $this->getJson("/api/v1/normal-ads/admin?user_id={$user1->id}");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
        
        // Filter by user2
        $response = $this->getJson("/api/v1/normal-ads/admin?user_id={$user2->id}");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_regular_user_cannot_access_admin_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum');
        
        $response = $this->getJson('/api/v1/normal-ads/admin');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized'
            ]);
    }

    public function test_unauthenticated_user_cannot_access_my_ads(): void
    {
        $response = $this->getJson('/api/v1/normal-ads/my-ads');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_admin_endpoint(): void
    {
        $response = $this->getJson('/api/v1/normal-ads/admin');

        $response->assertStatus(401);
    }
}