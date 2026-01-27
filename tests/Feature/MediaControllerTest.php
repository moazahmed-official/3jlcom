<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_authenticated_user_can_list_own_media(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Create media for current user
        $userMedia1 = Media::factory()->create(['user_id' => $user->id, 'type' => 'image']);
        $userMedia2 = Media::factory()->create(['user_id' => $user->id, 'type' => 'video']);
        
        // Create media for other user (should not appear)
        Media::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/v1/media');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'filename',
                        'path',
                        'url',
                        'type',
                        'status',
                        'user_id',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta'
            ]);

        // Should only return current user's media
        $this->assertEquals(2, count($response->json('data')));
        
        // Verify media belongs to authenticated user
        foreach ($response->json('data') as $media) {
            $this->assertEquals($user->id, $media['user_id']);
        }
    }

    public function test_media_index_can_filter_by_type(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Media::factory()->create(['user_id' => $user->id, 'type' => 'image']);
        Media::factory()->create(['user_id' => $user->id, 'type' => 'video']);
        Media::factory()->create(['user_id' => $user->id, 'type' => 'image']);

        $response = $this->getJson('/api/v1/media?type=image');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
        
        foreach ($response->json('data') as $media) {
            $this->assertEquals('image', $media['type']);
        }
    }

    public function test_media_index_can_filter_by_related_resource(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Media::factory()->create(['user_id' => $user->id, 'related_resource' => 'ads']);
        Media::factory()->create(['user_id' => $user->id, 'related_resource' => 'users']);
        Media::factory()->create(['user_id' => $user->id, 'related_resource' => 'ads']);

        $response = $this->getJson('/api/v1/media?related_resource=ads');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
        
        foreach ($response->json('data') as $media) {
            $this->assertEquals('ads', $media['related_resource']);
        }
    }

    public function test_unauthenticated_user_cannot_list_media(): void
    {
        $response = $this->getJson('/api/v1/media');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_upload_media(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
            'purpose' => 'profile',
            'related_resource' => 'users',
            'related_id' => $user->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'path',
                    'url',
                    'type',
                    'status',
                    'user_id',
                    'related_resource',
                    'related_id',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'type' => 'image',
            'status' => 'ready',
            'related_resource' => 'users',
            'related_id' => $user->id,
        ]);

        // Verify file was actually stored
        $media = Media::latest()->first();
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_unauthenticated_user_cannot_upload_media(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_validates_file_type(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_validates_file_size(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Create a file larger than 10MB
        $file = UploadedFile::fake()->create('large.jpg', 11 * 1024);

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_view_own_media(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $media = Media::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/v1/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'path',
                    'url',
                    'type',
                    'status',
                    'user_id',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    public function test_user_cannot_view_others_media(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $this->actingAs($user1, 'sanctum');

        $media = Media::factory()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->getJson("/api/v1/media/{$media->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_media(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Create media with actual file
        Storage::disk('public')->put('test/sample.jpg', 'fake content');
        
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'path' => 'test/sample.jpg',
        ]);

        $response = $this->deleteJson("/api/v1/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Media deleted successfully'
            ]);

        $this->assertDatabaseMissing('media', [
            'id' => $media->id,
        ]);

        // Verify file was deleted from storage
        Storage::disk('public')->assertMissing('test/sample.jpg');
    }

    public function test_user_can_update_own_media(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $media = Media::factory()->create([
            'user_id' => $user->id,
            'related_resource' => 'users',
            'related_id' => $user->id,
        ]);

        $response = $this->patchJson("/api/v1/media/{$media->id}", [
            'related_resource' => 'ads',
            'related_id' => 123,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'path',
                    'url',
                    'type',
                    'status',
                    'user_id',
                    'related_resource',
                    'related_id',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'related_resource' => 'ads',
            'related_id' => 123,
        ]);
    }

    public function test_user_cannot_update_others_media(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $this->actingAs($user1, 'sanctum');

        $media = Media::factory()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->patchJson("/api/v1/media/{$media->id}", [
            'related_resource' => 'ads',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_media_validates_input(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $media = Media::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->patchJson("/api/v1/media/{$media->id}", [
            'related_id' => -1, // Invalid: must be positive
            'related_resource' => str_repeat('a', 300), // Invalid: too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['related_id', 'related_resource']);
    }

    public function test_user_cannot_delete_others_media(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $this->actingAs($user1, 'sanctum');

        $media = Media::factory()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->deleteJson("/api/v1/media/{$media->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
        ]);
    }

    public function test_upload_organizes_files_by_purpose_and_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->image('brand-logo.jpg');

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
            'purpose' => 'brand',
        ]);

        $response->assertStatus(201);

        $media = Media::latest()->first();
        
        // Verify the path includes purpose and date structure
        $this->assertStringContainsString('brand/', $media->path);
        $this->assertStringContainsString(date('Y/m'), $media->path);
    }

    public function test_video_upload_detection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->create('video.mp4', 5000, 'video/mp4');

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'type' => 'video',
        ]);
    }
}