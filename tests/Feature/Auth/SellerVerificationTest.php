<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\SellerVerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminSellerVerificationRequestNotification;
use Laravel\Sanctum\Sanctum;

class SellerVerificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'user']);
    }

    public function test_seller_can_submit_verification_request()
    {
        Notification::fake();

        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        // Create seller user
        $seller = User::factory()->create([
            'account_type' => 'seller',
        ]);

        Sanctum::actingAs($seller);

        $documents = [
            [
                'type' => 'business_license',
                'url' => 'https://example.com/license.pdf',
                'description' => 'Business license document',
            ],
            [
                'type' => 'tax_certificate',
                'url' => 'https://example.com/tax.pdf',
            ],
        ];

        $response = $this->postJson('/api/v1/seller-verification', [
            'documents' => $documents,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'request_id',
                        'status',
                        'submitted_at'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ]);

        // Assert verification request was created
        $this->assertDatabaseHas('seller_verification_requests', [
            'user_id' => $seller->id,
            'status' => 'pending',
        ]);

        // Assert admin notification was sent
        Notification::assertSentTo($admin, AdminSellerVerificationRequestNotification::class);
    }

    public function test_seller_can_view_their_verification_request()
    {
        $seller = User::factory()->create([
            'account_type' => 'seller',
        ]);

        $verificationRequest = SellerVerificationRequest::create([
            'user_id' => $seller->id,
            'documents' => [
                [
                    'type' => 'business_license',
                    'url' => 'https://example.com/license.pdf',
                ],
            ],
            'status' => 'pending',
        ]);

        Sanctum::actingAs($seller);

        $response = $this->getJson('/api/v1/seller-verification');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'status',
                        'documents',
                        'submitted_at'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ]);
    }

    public function test_admin_can_approve_verification_request()
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        // Create seller user and verification request
        $seller = User::factory()->create([
            'account_type' => 'seller',
        ]);

        $verificationRequest = SellerVerificationRequest::create([
            'user_id' => $seller->id,
            'documents' => [
                [
                    'type' => 'business_license',
                    'url' => 'https://example.com/license.pdf',
                ],
            ],
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/seller-verification/{$verificationRequest->id}", [
            'status' => 'approved',
            'admin_comments' => 'All documents look good.',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'request_id',
                        'status',
                        'admin_comments',
                        'verified_at',
                        'verified_by'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ]);

        // Assert verification request was approved
        $verificationRequest->refresh();
        $this->assertEquals('approved', $verificationRequest->status);
        $this->assertEquals('All documents look good.', $verificationRequest->admin_comments);
        $this->assertEquals($admin->id, $verificationRequest->verified_by);
    }

    public function test_admin_can_reject_verification_request()
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        // Create seller user and verification request
        $seller = User::factory()->create([
            'account_type' => 'seller',
        ]);

        $verificationRequest = SellerVerificationRequest::create([
            'user_id' => $seller->id,
            'documents' => [
                [
                    'type' => 'business_license',
                    'url' => 'https://example.com/license.pdf',
                ],
            ],
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/seller-verification/{$verificationRequest->id}", [
            'status' => 'rejected',
            'admin_comments' => 'Documents are not clear enough.',
        ]);

        $response->assertStatus(200);

        // Assert verification request was rejected
        $verificationRequest->refresh();
        $this->assertEquals('rejected', $verificationRequest->status);
        $this->assertEquals('Documents are not clear enough.', $verificationRequest->admin_comments);
    }

    public function test_user_verification_endpoint_works_for_admin()
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());

        // Create seller user and verification request
        $seller = User::factory()->create([
            'account_type' => 'seller',
        ]);

        $verificationRequest = SellerVerificationRequest::create([
            'user_id' => $seller->id,
            'documents' => [
                [
                    'type' => 'business_license',
                    'url' => 'https://example.com/license.pdf',
                ],
            ],
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/users/{$seller->id}/verify", [
            'status' => 'approved',
            'admin_comments' => 'Verification approved through user endpoint.',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'user_id',
                        'verification_status',
                        'admin_comments',
                        'verified_at'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                ]);

        // Assert verification request was approved
        $verificationRequest->refresh();
        $this->assertEquals('approved', $verificationRequest->status);
    }

    public function test_non_seller_cannot_submit_verification_request()
    {
        $individual = User::factory()->create([
            'account_type' => 'individual',
        ]);

        Sanctum::actingAs($individual);

        $response = $this->postJson('/api/v1/seller-verification', [
            'documents' => [
                [
                    'type' => 'business_license',
                    'url' => 'https://example.com/license.pdf',
                ],
            ],
        ]);

        $response->assertStatus(403); // Forbidden
    }

    public function test_non_admin_cannot_approve_verification_request()
    {
        // Create regular user
        $user = User::factory()->create();

        $verificationRequest = SellerVerificationRequest::create([
            'user_id' => $user->id,
            'documents' => [
                [
                    'type' => 'business_license',
                    'url' => 'https://example.com/license.pdf',
                ],
            ],
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/seller-verification/{$verificationRequest->id}", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403); // Forbidden
    }
}