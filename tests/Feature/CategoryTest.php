<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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
    public function admin_can_list_categories()
    {
        Category::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'page',
                    'per_page',
                    'total',
                    'items' => [
                        '*' => ['id', 'name_en', 'name_ar', 'status', 'specs_group_id', 'created_at', 'updated_at']
                    ]
                ]
            ])
            ->assertJsonPath('data.total', 5);
    }

    /** @test */
    public function regular_user_cannot_list_categories()
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson('/api/v1/admin/categories');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }

    /** @test */
    public function admin_can_view_single_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name_en', $category->name_en);
    }

    /** @test */
    public function regular_user_cannot_view_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_category()
    {
        $categoryData = [
            'name_en' => 'Electronics',
            'name_ar' => 'إلكترونيات',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name_en', 'Electronics')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('categories', [
            'name_en' => 'Electronics',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function regular_user_cannot_create_category()
    {
        $categoryData = [
            'name_en' => 'Test Category',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/v1/admin/categories', $categoryData);

        $response->assertStatus(403);
    }

    /** @test */
    public function creating_category_requires_name_en()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name_en']);
    }

    /** @test */
    public function creating_category_validates_status_enum()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/categories', [
                'name_en' => 'Test Category',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function admin_can_update_category()
    {
        $category = Category::factory()->create([
            'name_en' => 'Old Name',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'name_en' => 'New Name',
                'status' => 'inactive',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name_en', 'New Name')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name_en' => 'New Name',
            'status' => 'inactive',
        ]);
    }

    /** @test */
    public function regular_user_cannot_update_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'name_en' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    /** @test */
    public function regular_user_cannot_delete_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function can_search_categories()
    {
        Category::factory()->create(['name_en' => 'Electronics']);
        Category::factory()->create(['name_en' => 'Clothing']);
        Category::factory()->create(['name_en' => 'Electronic Devices']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/categories?search=Electronic');

        $response->assertOk();
        $data = $response->json('data.items');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function can_filter_categories_by_status()
    {
        Category::factory()->count(3)->create(['status' => 'active']);
        Category::factory()->count(2)->inactive()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/categories?status=inactive');

        $response->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    /** @test */
    public function categories_can_be_sorted()
    {
        Category::factory()->create(['name_en' => 'Zebra']);
        Category::factory()->create(['name_en' => 'Apple']);
        Category::factory()->create(['name_en' => 'Mango']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/categories?sort_by=name_en&sort_order=asc');

        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertEquals('Apple', $items[0]['name_en']);
        $this->assertEquals('Mango', $items[1]['name_en']);
        $this->assertEquals('Zebra', $items[2]['name_en']);
    }

    /** @test */
    public function guest_cannot_access_categories()
    {
        $response = $this->getJson('/api/v1/admin/categories');
        $response->assertStatus(401);
    }
}
