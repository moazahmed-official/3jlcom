<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Role;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SliderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Media $media;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        // Create regular user
        $this->user = User::factory()->create();
        $this->user->roles()->attach($userRole);

        // Create media for testing
        $this->media = Media::factory()->create();
    }

    // ==================== INDEX TESTS ====================

    public function test_public_can_list_active_sliders(): void
    {
        Slider::factory()->active()->count(3)->create();
        Slider::factory()->inactive()->count(2)->create();

        $response = $this->getJson('/api/v1/sliders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'image_url', 'status', 'is_active']
                ]
            ]);

        // Should only see active sliders
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_list_all_sliders_with_include_inactive(): void
    {
        Slider::factory()->active()->count(3)->create();
        Slider::factory()->inactive()->count(2)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/sliders?include_inactive=1');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_sliders_are_ordered_by_id(): void
    {
        $slider1 = Slider::factory()->active()->create(['name' => 'First']);
        $slider2 = Slider::factory()->active()->create(['name' => 'Second']);
        $slider3 = Slider::factory()->active()->create(['name' => 'Third']);

        $response = $this->getJson('/api/v1/sliders');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('First', $data[0]['name']);
        $this->assertEquals('Second', $data[1]['name']);
        $this->assertEquals('Third', $data[2]['name']);
    }

    // ==================== SHOW TESTS ====================

    public function test_public_can_view_active_slider(): void
    {
        $slider = Slider::factory()->active()->create();

        $response = $this->getJson("/api/v1/sliders/{$slider->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $slider->id,
                    'name' => $slider->name,
                    'status' => 'active'
                ]
            ]);
    }

    public function test_public_cannot_view_inactive_slider(): void
    {
        $slider = Slider::factory()->inactive()->create();

        $response = $this->getJson("/api/v1/sliders/{$slider->id}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'code' => 404,
                'message' => 'Slider not found'
            ]);
    }

    public function test_admin_can_view_inactive_slider(): void
    {
        $slider = Slider::factory()->inactive()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/sliders/{$slider->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $slider->id,
                    'status' => 'inactive'
                ]
            ]);
    }

    public function test_show_returns_404_for_nonexistent_slider(): void
    {
        $response = $this->getJson('/api/v1/sliders/99999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'code' => 404,
                'message' => 'Slider not found'
            ]);
    }

    // ==================== STORE TESTS ====================

    public function test_admin_can_create_slider(): void
    {
        $data = [
            'name' => 'Test Slider',
            'image_id' => $this->media->id,
            'value' => 'https://example.com/promo',
            'status' => 'active'
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/sliders', $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Slider created successfully',
                'data' => [
                    'name' => 'Test Slider',
                    'status' => 'active'
                ]
            ]);

        $this->assertDatabaseHas('sliders', [
            'name' => 'Test Slider',
        ]);
    }

    public function test_non_admin_cannot_create_slider(): void
    {
        $data = [
            'name' => 'Test Slider',
            'image_id' => $this->media->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/sliders', $data);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized'
            ]);
    }

    public function test_unauthenticated_cannot_create_slider(): void
    {
        $data = [
            'name' => 'Test Slider',
            'image_id' => $this->media->id,
        ];

        $response = $this->postJson('/api/v1/sliders', $data);

        $response->assertStatus(401);
    }

    public function test_create_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/sliders', []);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed'
            ])
            ->assertJsonValidationErrors(['name', 'image_id']);
    }

    public function test_create_validates_media_exists(): void
    {
        $data = [
            'name' => 'Test Slider',
            'image_id' => 99999,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/sliders', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image_id']);
    }

    public function test_create_defaults_status_to_active(): void
    {
        $data = [
            'name' => 'Test Slider',
            'image_id' => $this->media->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/sliders', $data);

        $response->assertStatus(201);
        $this->assertEquals('active', $response->json('data.status'));
    }

    // ==================== UPDATE TESTS ====================

    public function test_admin_can_update_slider(): void
    {
        $slider = Slider::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/sliders/{$slider->id}", [
                'name' => 'Updated Slider Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Slider updated successfully',
                'data' => [
                    'name' => 'Updated Slider Name',
                ]
            ]);

        $this->assertDatabaseHas('sliders', [
            'id' => $slider->id,
            'name' => 'Updated Slider Name',
        ]);
    }

    public function test_non_admin_cannot_update_slider(): void
    {
        $slider = Slider::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/sliders/{$slider->id}", [
                'name' => 'Updated Name'
            ]);

        $response->assertStatus(403);
    }

    public function test_update_returns_404_for_nonexistent_slider(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/sliders/99999', [
                'name' => 'Updated Name'
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'code' => 404
            ]);
    }

    // ==================== DELETE TESTS ====================

    public function test_admin_can_delete_slider(): void
    {
        $slider = Slider::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/sliders/{$slider->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Slider deleted successfully'
            ]);

        $this->assertDatabaseMissing('sliders', ['id' => $slider->id]);
    }

    public function test_non_admin_cannot_delete_slider(): void
    {
        $slider = Slider::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/sliders/{$slider->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('sliders', ['id' => $slider->id]);
    }

    public function test_delete_returns_404_for_nonexistent_slider(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/v1/sliders/99999');

        $response->assertStatus(404);
    }

    // ==================== ACTIVATE TESTS ====================

    public function test_admin_can_activate_slider(): void
    {
        $slider = Slider::factory()->inactive()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/sliders/{$slider->id}/actions/activate");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Slider activated successfully',
                'data' => [
                    'status' => 'active',
                    'is_active' => true
                ]
            ]);

        $this->assertDatabaseHas('sliders', [
            'id' => $slider->id,
            'status' => 'active'
        ]);
    }

    public function test_activate_returns_error_if_already_active(): void
    {
        $slider = Slider::factory()->active()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/sliders/{$slider->id}/actions/activate");

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'code' => 400,
                'message' => 'Slider is already active'
            ]);
    }

    public function test_non_admin_cannot_activate_slider(): void
    {
        $slider = Slider::factory()->inactive()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/sliders/{$slider->id}/actions/activate");

        $response->assertStatus(403);
    }

    // ==================== DEACTIVATE TESTS ====================

    public function test_admin_can_deactivate_slider(): void
    {
        $slider = Slider::factory()->active()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/sliders/{$slider->id}/actions/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Slider deactivated successfully',
                'data' => [
                    'status' => 'inactive',
                    'is_active' => false
                ]
            ]);

        $this->assertDatabaseHas('sliders', [
            'id' => $slider->id,
            'status' => 'inactive'
        ]);
    }

    public function test_deactivate_returns_error_if_already_inactive(): void
    {
        $slider = Slider::factory()->inactive()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/sliders/{$slider->id}/actions/deactivate");

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'code' => 400,
                'message' => 'Slider is already inactive'
            ]);
    }

    public function test_non_admin_cannot_deactivate_slider(): void
    {
        $slider = Slider::factory()->active()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/sliders/{$slider->id}/actions/deactivate");

        $response->assertStatus(403);
    }

    public function test_deactivate_returns_404_for_nonexistent_slider(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/sliders/99999/actions/deactivate');

        $response->assertStatus(404);
    }
}
