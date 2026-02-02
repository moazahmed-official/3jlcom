<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Notifications\ReviewReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin', 'permissions' => []]);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        // Create regular user
        $this->user = User::factory()->create();
    }

    // =====================
    // LIST NOTIFICATIONS TESTS
    // =====================

    public function test_user_can_list_their_notifications()
    {
        // Create some notifications for the user
        $this->user->notify(new AdminNotification([
            'title' => 'Test Notification 1',
            'body' => 'This is a test notification',
        ]));

        $this->user->notify(new AdminNotification([
            'title' => 'Test Notification 2',
            'body' => 'This is another test notification',
        ]));

        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'page',
                    'per_page',
                    'total',
                    'items' => [
                        '*' => ['id', 'type', 'title', 'body', 'data', 'read', 'created_at']
                    ],
                    'unread_count'
                ]
            ])
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_user_can_filter_unread_notifications()
    {
        // Create read and unread notifications
        $notification1 = $this->user->notify(new AdminNotification([
            'title' => 'Read Notification',
            'body' => 'This has been read',
        ]));

        $this->user->notify(new AdminNotification([
            'title' => 'Unread Notification',
            'body' => 'This has not been read',
        ]));

        // Mark first notification as read
        $this->user->notifications()->first()->markAsRead();

        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications?read=false');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_unauthenticated_user_cannot_list_notifications()
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }

    // =====================
    // VIEW NOTIFICATION TESTS
    // =====================

    public function test_user_can_view_their_notification()
    {
        $this->user->notify(new AdminNotification([
            'title' => 'Test Notification',
            'body' => 'This is a detailed test notification',
            'action_url' => 'https://example.com/action',
        ]));

        $notification = $this->user->notifications()->first();

        $response = $this->actingAs($this->user)->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Test Notification')
            ->assertJsonPath('data.body', 'This is a detailed test notification');
    }

    public function test_user_cannot_view_others_notification()
    {
        $otherUser = User::factory()->create();
        $otherUser->notify(new AdminNotification([
            'title' => 'Private Notification',
            'body' => 'This belongs to another user',
        ]));

        $notification = $otherUser->notifications()->first();

        $response = $this->actingAs($this->user)->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    public function test_viewing_nonexistent_notification_returns_404()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // =====================
    // MARK AS READ TESTS
    // =====================

    public function test_user_can_mark_notification_as_read()
    {
        $this->user->notify(new AdminNotification([
            'title' => 'Unread Notification',
            'body' => 'This will be marked as read',
        ]));

        $notification = $this->user->notifications()->first();
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->user)->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('data.read', true);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read()
    {
        // Create multiple unread notifications
        $this->user->notify(new AdminNotification(['title' => 'Notification 1', 'body' => 'Body 1']));
        $this->user->notify(new AdminNotification(['title' => 'Notification 2', 'body' => 'Body 2']));
        $this->user->notify(new AdminNotification(['title' => 'Notification 3', 'body' => 'Body 3']));

        $this->assertEquals(3, $this->user->unreadNotifications()->count());

        $response = $this->actingAs($this->user)->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJsonPath('data.marked_count', 3);

        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    // =====================
    // DELETE NOTIFICATION TESTS
    // =====================

    public function test_user_can_delete_their_notification()
    {
        $this->user->notify(new AdminNotification([
            'title' => 'To Be Deleted',
            'body' => 'This notification will be deleted',
        ]));

        $notification = $this->user->notifications()->first();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200);

        $this->assertEquals(0, $this->user->notifications()->count());
    }

    public function test_user_cannot_delete_others_notification()
    {
        $otherUser = User::factory()->create();
        $otherUser->notify(new AdminNotification([
            'title' => 'Protected Notification',
            'body' => 'This cannot be deleted by others',
        ]));

        $notification = $otherUser->notifications()->first();

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(404);

        // Notification should still exist
        $this->assertEquals(1, $otherUser->notifications()->count());
    }

    // =====================
    // ADMIN SEND NOTIFICATION TESTS
    // =====================

    public function test_admin_can_send_notification_to_user()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/notifications/send', [
            'target' => 'user',
            'target_id' => $this->user->id,
            'title' => 'Admin Message',
            'body' => 'This is an important message from admin',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recipients_count', 1);

        // Check user received the notification
        $this->assertEquals(1, $this->user->notifications()->count());
        
        $notification = $this->user->notifications()->first();
        $this->assertEquals('Admin Message', $notification->data['title']);
    }

    public function test_admin_can_send_notification_to_role_group()
    {
        // Create users with a specific role
        $dealerRole = Role::create(['name' => 'dealer', 'permissions' => []]);
        
        $dealer1 = User::factory()->create();
        $dealer1->roles()->attach($dealerRole);
        
        $dealer2 = User::factory()->create();
        $dealer2->roles()->attach($dealerRole);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/notifications/send', [
            'target' => 'group',
            'target_role' => 'dealer',
            'title' => 'Dealer Announcement',
            'body' => 'Important message for all dealers',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.recipients_count', 2);

        // Check both dealers received the notification
        $this->assertEquals(1, $dealer1->notifications()->count());
        $this->assertEquals(1, $dealer2->notifications()->count());
    }

    public function test_regular_user_cannot_send_notifications()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/notifications/send', [
            'target' => 'user',
            'target_id' => $this->admin->id,
            'title' => 'Unauthorized Message',
            'body' => 'This should not be allowed',
        ]);

        $response->assertStatus(403);
    }

    public function test_send_notification_validates_target_user()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/notifications/send', [
            'target' => 'user',
            'target_id' => 99999, // Non-existent user
            'title' => 'Test',
            'body' => 'Test message',
        ]);

        $response->assertStatus(404);
    }

    public function test_send_notification_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/notifications/send', [
            'target' => 'user',
            'target_id' => $this->user->id,
            // Missing title and body
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'body']);
    }

    // =====================
    // NOTIFICATION DATA TESTS
    // =====================

    public function test_notification_includes_custom_data()
    {
        $this->user->notify(new AdminNotification([
            'title' => 'Data Test',
            'body' => 'Testing custom data',
            'data' => [
                'ad_id' => 123,
                'action' => 'view_ad',
            ],
            'action_url' => 'https://example.com/ads/123',
        ]));

        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        
        // The nested 'data' key from AdminNotification is preserved in the response
        $notificationData = $response->json('data.items.0.data.data');
        $this->assertArrayHasKey('ad_id', $notificationData);
        $this->assertEquals(123, $notificationData['ad_id']);
    }

    public function test_sensitive_data_is_filtered()
    {
        // Manually create a notification with sensitive data in array
        $this->user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => AdminNotification::class,
            'data' => [
                'title' => 'Test',
                'body' => 'Test body',
                'otp' => '123456', // Sensitive - should be filtered
                'password' => 'secret', // Sensitive - should be filtered
                'safe_data' => 'this should appear',
            ],
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        
        $notificationData = $response->json('data.items.0.data');
        $this->assertArrayNotHasKey('otp', $notificationData);
        $this->assertArrayNotHasKey('password', $notificationData);
        $this->assertArrayHasKey('safe_data', $notificationData);
    }

    // =====================
    // PAGINATION TESTS
    // =====================

    public function test_notifications_are_paginated()
    {
        // Create 25 notifications
        for ($i = 0; $i < 25; $i++) {
            $this->user->notify(new AdminNotification([
                'title' => "Notification {$i}",
                'body' => "Body {$i}",
            ]));
        }

        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications?limit=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.items')
            ->assertJsonPath('data.total', 25)
            ->assertJsonPath('data.per_page', 10);
    }

    public function test_notifications_are_ordered_by_newest_first()
    {
        $this->user->notify(new AdminNotification(['title' => 'First', 'body' => 'First created']));
        sleep(1); // Ensure different timestamps
        $this->user->notify(new AdminNotification(['title' => 'Second', 'body' => 'Second created']));

        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        
        $notifications = $response->json('data.items');
        $this->assertEquals('Second', $notifications[0]['title']);
        $this->assertEquals('First', $notifications[1]['title']);
    }
}
