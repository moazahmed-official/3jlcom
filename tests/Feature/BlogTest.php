<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole->id);

        // Create regular user
        $this->user = User::factory()->create();
        $this->user->roles()->attach($userRole->id);
    }

    /** @test */
    public function guest_can_list_published_blogs()
    {
        Blog::factory()->count(3)->create(['status' => 'published']);
        Blog::factory()->count(2)->draft()->create();

        $response = $this->getJson('/api/v1/blogs');

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
                            'title',
                            'body',
                            'status',
                            'created_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.total', 3);
    }

    /** @test */
    public function guest_can_view_published_blog()
    {
        $blog = Blog::factory()->create(['status' => 'published']);

        $response = $this->getJson("/api/v1/blogs/{$blog->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $blog->id,
                    'title' => $blog->title,
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_view_draft_blog()
    {
        $blog = Blog::factory()->draft()->create();

        $response = $this->getJson("/api/v1/blogs/{$blog->id}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Blog not found',
            ]);
    }

    /** @test */
    public function admin_can_create_blog()
    {
        Sanctum::actingAs($this->admin);

        $blogData = [
            'title' => 'Test Blog',
            'body' => 'This is a test blog post content.',
            'status' => 'published',
        ];

        $response = $this->postJson('/api/v1/admin/blogs', $blogData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Blog created successfully',
            ])
            ->assertJsonPath('data.title', 'Test Blog');

        $this->assertDatabaseHas('blogs', [
            'title' => 'Test Blog',
        ]);
    }

    /** @test */
    public function regular_user_cannot_create_blog()
    {
        Sanctum::actingAs($this->user);

        $blogData = [
            'title' => 'Test Blog',
            'body' => 'This is a test blog post content.',
            'status' => 'published',
        ];

        $response = $this->postJson('/api/v1/admin/blogs', $blogData);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized',
            ]);
    }

    /** @test */
    public function creating_blog_requires_title_and_body()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/blogs', [
            'status' => 'draft',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'body']);
    }

    /** @test */
    public function admin_can_list_all_blogs()
    {
        Sanctum::actingAs($this->admin);

        Blog::factory()->count(3)->create(['status' => 'published']);
        Blog::factory()->count(2)->draft()->create();

        $response = $this->getJson('/api/v1/admin/blogs');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 5);
    }

    /** @test */
    public function admin_can_filter_blogs_by_status()
    {
        Sanctum::actingAs($this->admin);

        Blog::factory()->count(3)->create(['status' => 'published']);
        Blog::factory()->count(2)->draft()->create();

        $response = $this->getJson('/api/v1/admin/blogs?status=draft');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 2);
    }

    /** @test */
    public function regular_user_cannot_list_all_blogs()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/admin/blogs');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized',
            ]);
    }

    /** @test */
    public function admin_can_view_any_blog()
    {
        Sanctum::actingAs($this->admin);

        $blog = Blog::factory()->draft()->create();

        $response = $this->getJson("/api/v1/admin/blogs/{$blog->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $blog->id,
                ],
            ]);
    }

    /** @test */
    public function admin_can_update_blog()
    {
        Sanctum::actingAs($this->admin);

        $blog = Blog::factory()->create();

        $response = $this->putJson("/api/v1/admin/blogs/{$blog->id}", [
            'title' => 'Updated Title',
            'status' => 'archived',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Blog updated successfully',
            ])
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', 'archived');

        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function regular_user_cannot_update_blog()
    {
        Sanctum::actingAs($this->user);

        $blog = Blog::factory()->create();

        $response = $this->putJson("/api/v1/admin/blogs/{$blog->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized',
            ]);
    }

    /** @test */
    public function admin_can_delete_blog()
    {
        Sanctum::actingAs($this->admin);

        $blog = Blog::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/blogs/{$blog->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Blog deleted successfully',
            ]);

        $this->assertSoftDeleted('blogs', [
            'id' => $blog->id,
        ]);
    }

    /** @test */
    public function regular_user_cannot_delete_blog()
    {
        Sanctum::actingAs($this->user);

        $blog = Blog::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/blogs/{$blog->id}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized',
            ]);
    }

    /** @test */
    public function can_search_blogs()
    {
        Blog::factory()->create(['title' => 'Laravel Tutorial', 'status' => 'published']);
        Blog::factory()->create(['title' => 'PHP Best Practices', 'status' => 'published']);

        $response = $this->getJson('/api/v1/blogs?search=Laravel');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.title', 'Laravel Tutorial');
    }

    /** @test */
    public function blogs_can_be_sorted()
    {
        $blog1 = Blog::factory()->create(['title' => 'A Blog', 'status' => 'published']);
        $blog2 = Blog::factory()->create(['title' => 'Z Blog', 'status' => 'published']);

        $response = $this->getJson('/api/v1/blogs?sort_by=title&sort_order=asc');

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.title', 'A Blog')
            ->assertJsonPath('data.items.1.title', 'Z Blog');
    }
}
