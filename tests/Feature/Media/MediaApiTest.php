<?php

namespace Tests\Feature\Media;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_upload_image_successfully()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(1000);

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
            'purpose' => 'ad',
            'related_resource' => 'ads',
            'related_id' => 1,
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message', 
                    'data' => [
                        'id',
                        'filename',
                        'original_name',
                        'url',
                        'thumbnail_url',
                        'type',
                        'size',
                        'purpose',
                        'status',
                        'created_at',
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                    'data' => [
                        'type' => 'image',
                        'purpose' => 'ad',
                        'status' => 'ready',
                    ]
                ]);

        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'type' => 'image',
            'purpose' => 'ad',
            'related_resource' => 'ads',
            'related_id' => 1,
        ]);

        // Check file was stored
        $media = Media::first();
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_user_can_upload_video_successfully()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('test.mp4', 5000, 'video/mp4');

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
            'purpose' => 'profile',
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'status' => 'success',
                    'data' => [
                        'type' => 'video',
                        'purpose' => 'profile',
                    ]
                ]);
    }

    public function test_upload_fails_with_invalid_file_type()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_fails_with_file_too_large()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('large.jpg', 11000, 'image/jpeg');

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
    }

    public function test_unauthorized_user_cannot_upload_media()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_retrieve_media()
    {
        $user = User::factory()->create();
        $media = Media::factory()->image()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/media/{$media->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'data' => [
                        'id' => $media->id,
                        'type' => 'image',
                    ]
                ]);
    }

    public function test_user_can_delete_own_media()
    {
        $user = User::factory()->create();
        $media = Media::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        // Create fake file for deletion test
        Storage::disk('public')->put($media->path, 'fake content');

        $response = $this->deleteJson("/api/v1/media/{$media->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Media deleted successfully',
                ]);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_user_cannot_delete_others_media()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $media = Media::factory()->create(['user_id' => $owner->id]);
        
        Sanctum::actingAs($other);

        $response = $this->deleteJson("/api/v1/media/{$media->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_admin_can_delete_any_media()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $user = User::factory()->create();
        $media = Media::factory()->create(['user_id' => $user->id]);
        
        Sanctum::actingAs($admin);
        Storage::disk('public')->put($media->path, 'fake content');

        $response = $this->deleteJson("/api/v1/media/{$media->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_media_not_found_returns_404()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/media/999');

        $response->assertStatus(404);
    }

    public function test_upload_validation_messages()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/media', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file'])
                ->assertJsonFragment([
                    'file' => ['A file is required.']
                ]);
    }

    public function test_invalid_purpose_validation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/v1/media', [
            'file' => $file,
            'purpose' => 'invalid_purpose',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['purpose']);
    }
}