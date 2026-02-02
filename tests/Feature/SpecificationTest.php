<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Specification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role
        $adminRole = Role::create(['name' => 'admin']);
        
        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        // Create regular user
        $this->regularUser = User::factory()->create();
    }

    /** @test */
    public function admin_can_list_specifications()
    {
        Specification::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/specifications');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'page',
                    'per_page',
                    'total',
                    'items' => [
                        '*' => ['id', 'name_en', 'name_ar', 'type', 'values', 'image_id', 'created_at', 'updated_at']
                    ]
                ]
            ])
            ->assertJsonPath('data.total', 5);
    }

    /** @test */
    public function regular_user_cannot_list_specifications()
    {
        Specification::factory()->count(3)->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson('/api/v1/admin/specifications');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }

    /** @test */
    public function admin_can_view_single_specification()
    {
        $specification = Specification::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/specifications/{$specification->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $specification->id)
            ->assertJsonPath('data.name_en', $specification->name_en);
    }

    /** @test */
    public function regular_user_cannot_view_specification()
    {
        $specification = Specification::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson("/api/v1/admin/specifications/{$specification->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_specification()
    {
        $specificationData = [
            'name_en' => 'Transmission',
            'name_ar' => 'ناقل الحركة',
            'type' => 'select',
            'values' => ['Automatic', 'Manual', 'Semi-Automatic'],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/specifications', $specificationData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name_en', 'Transmission')
            ->assertJsonPath('data.type', 'select');

        $this->assertDatabaseHas('specifications', [
            'name_en' => 'Transmission',
            'type' => 'select',
        ]);
    }

    /** @test */
    public function regular_user_cannot_create_specification()
    {
        $specificationData = [
            'name_en' => 'Test Spec',
            'type' => 'text',
        ];

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/v1/admin/specifications', $specificationData);

        $response->assertStatus(403);
    }

    /** @test */
    public function creating_specification_requires_name_en_and_type()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/specifications', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name_en', 'type']);
    }

    /** @test */
    public function creating_specification_validates_type_enum()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/specifications', [
                'name_en' => 'Test Spec',
                'type' => 'invalid_type',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function admin_can_update_specification()
    {
        $specification = Specification::factory()->create([
            'name_en' => 'Old Name',
            'type' => 'text',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/specifications/{$specification->id}", [
                'name_en' => 'New Name',
                'type' => 'number',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name_en', 'New Name')
            ->assertJsonPath('data.type', 'number');

        $this->assertDatabaseHas('specifications', [
            'id' => $specification->id,
            'name_en' => 'New Name',
            'type' => 'number',
        ]);
    }

    /** @test */
    public function regular_user_cannot_update_specification()
    {
        $specification = Specification::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->putJson("/api/v1/admin/specifications/{$specification->id}", [
                'name_en' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_specification()
    {
        $specification = Specification::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/specifications/{$specification->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('specifications', [
            'id' => $specification->id,
        ]);
    }

    /** @test */
    public function regular_user_cannot_delete_specification()
    {
        $specification = Specification::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->deleteJson("/api/v1/admin/specifications/{$specification->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function can_search_specifications()
    {
        Specification::factory()->create(['name_en' => 'Engine Type']);
        Specification::factory()->create(['name_en' => 'Fuel Type']);
        Specification::factory()->create(['name_en' => 'Transmission']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/specifications?search=Type');

        $response->assertOk();
        $data = $response->json('data.items');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function can_filter_specifications_by_type()
    {
        Specification::factory()->count(3)->create(['type' => 'text']);
        Specification::factory()->count(2)->select()->create();
        Specification::factory()->count(1)->number()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/specifications?type=select');

        $response->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    /** @test */
    public function specifications_can_be_sorted()
    {
        Specification::factory()->create(['name_en' => 'Zebra']);
        Specification::factory()->create(['name_en' => 'Apple']);
        Specification::factory()->create(['name_en' => 'Mango']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/specifications?sort_by=name_en&sort_order=asc');

        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertEquals('Apple', $items[0]['name_en']);
        $this->assertEquals('Mango', $items[1]['name_en']);
        $this->assertEquals('Zebra', $items[2]['name_en']);
    }

    /** @test */
    public function guest_cannot_access_specifications()
    {
        $response = $this->getJson('/api/v1/admin/specifications');
        $response->assertStatus(401);
    }
}
