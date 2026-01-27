<?php

namespace Tests\Feature\Brands;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class BrandApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'user']);
    }

    public function test_public_can_list_brands()
    {
        // Create test brands
        Brand::factory()->create(['name_en' => 'Toyota', 'name_ar' => 'تويوتا']);
        Brand::factory()->create(['name_en' => 'Nissan', 'name_ar' => 'نيسان']);
        Brand::factory()->create(['name_en' => 'BMW', 'name_ar' => 'بي إم دبليو']);

        $response = $this->getJson('/api/v1/brands');

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
                                'name_en',
                                'name_ar',
                                'created_at'
                            ]
                        ]
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ])
                ->assertJsonPath('data.total', 3);
    }

    public function test_admin_can_create_brand_success()
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        Sanctum::actingAs($admin);

        $brandData = [
            'name_en' => 'Mercedes',
            'name_ar' => 'مرسيدس'
        ];

        $response = $this->postJson('/api/v1/brands', $brandData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name_en',
                        'name_ar',
                        'created_at'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Brand created successfully',
                    'data' => $brandData
                ]);

        $this->assertDatabaseHas('brands', $brandData);
    }

    public function test_create_brand_validation_errors()
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/brands', []);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'name_en',
                        'name_ar'
                    ]
                ]);
    }

    public function test_non_admin_cannot_create_brand()
    {
        // Create regular user
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'user')->first());

        Sanctum::actingAs($user);

        $brandData = [
            'name_en' => 'Mercedes',
            'name_ar' => 'مرسيدس'
        ];

        $response = $this->postJson('/api/v1/brands', $brandData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('brands', $brandData);
    }

    public function test_public_can_list_models_for_brand()
    {
        // Create brand and models
        $brand = Brand::factory()->create(['name_en' => 'Toyota']);
        
        CarModel::factory()->create([
            'brand_id' => $brand->id,
            'name_en' => 'Camry',
            'name_ar' => 'كامري'
        ]);
        
        CarModel::factory()->create([
            'brand_id' => $brand->id,
            'name_en' => 'Corolla',
            'name_ar' => 'كورولا'
        ]);

        $response = $this->getJson("/api/v1/brands/{$brand->id}/models");

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
                                'brand_id',
                                'name_en',
                                'name_ar',
                                'year_from',
                                'year_to',
                                'created_at'
                            ]
                        ]
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ])
                ->assertJsonPath('data.total', 2);
    }

    public function test_admin_can_create_model_success()
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        // Create brand
        $brand = Brand::factory()->create(['name_en' => 'Toyota']);

        Sanctum::actingAs($admin);

        $modelData = [
            'name_en' => 'Prius',
            'name_ar' => 'بريوس',
            'year_from' => 2010,
            'year_to' => 2023
        ];

        $response = $this->postJson("/api/v1/brands/{$brand->id}/models", $modelData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'brand_id',
                        'name_en',
                        'name_ar',
                        'year_from',
                        'year_to',
                        'created_at'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Model created successfully'
                ]);

        $this->assertDatabaseHas('models', array_merge($modelData, [
            'brand_id' => $brand->id
        ]));
    }

    public function test_create_model_validation_and_years()
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        // Create brand
        $brand = Brand::factory()->create(['name_en' => 'Toyota']);

        Sanctum::actingAs($admin);

        // Test invalid year range
        $invalidData = [
            'name_en' => 'Prius',
            'name_ar' => 'بريوس',
            'year_from' => 2020,
            'year_to' => 2010  // Invalid: year_to < year_from
        ];

        $response = $this->postJson("/api/v1/brands/{$brand->id}/models", $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'year_to'
                    ]
                ]);
    }

    public function test_non_admin_cannot_create_model()
    {
        // Create regular user
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'user')->first());

        // Create brand
        $brand = Brand::factory()->create(['name_en' => 'Toyota']);

        Sanctum::actingAs($user);

        $modelData = [
            'name_en' => 'Prius',
            'name_ar' => 'بريوس'
        ];

        $response = $this->postJson("/api/v1/brands/{$brand->id}/models", $modelData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('models', array_merge($modelData, [
            'brand_id' => $brand->id
        ]));
    }

    public function test_models_endpoint_returns_404_for_nonexistent_brand()
    {
        $response = $this->getJson("/api/v1/brands/999/models");

        $response->assertStatus(404);
    }

    public function test_create_model_returns_404_for_nonexistent_brand()
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        Sanctum::actingAs($admin);

        $modelData = [
            'name_en' => 'Prius',
            'name_ar' => 'بريوس'
        ];

        $response = $this->postJson("/api/v1/brands/999/models", $modelData);

        $response->assertStatus(404);
    }
}