<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $admin;
    protected User $moderator;
    protected Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->moderator = User::factory()->create();
        $this->moderator->assignRole('moderator');
        $this->ad = Ad::factory()->create();
    }

    /** @test */
    public function authenticated_user_can_create_report_for_ad()
    {
        $reportData = [
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'reason' => 'spam',
            'title' => 'Spam ad',
            'details' => 'This ad is clearly spam and should be removed.',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', $reportData);

        $response->assertStatus(201)
            ->assertJsonPath('data.report.reason', 'spam')
            ->assertJsonPath('data.report.status', Report::STATUS_OPEN);

        $this->assertDatabaseHas('reports', [
            'reported_by_user_id' => $this->user->id,
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'reason' => 'spam',
            'status' => Report::STATUS_OPEN,
        ]);
    }

    /** @test */
    public function authenticated_user_can_create_report_for_user()
    {
        $reportedUser = User::factory()->create();

        $reportData = [
            'target_type' => 'user',
            'target_id' => $reportedUser->id,
            'reason' => 'fraud',
            'title' => 'Fraudulent user',
            'details' => 'This user is engaging in fraudulent activities.',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', $reportData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reports', [
            'target_type' => 'user',
            'target_id' => $reportedUser->id,
            'reason' => 'fraud',
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_report()
    {
        $reportData = [
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'reason' => 'spam',
            'title' => 'Test',
            'details' => 'Test details',
        ];

        $response = $this->postJson('/api/v1/reports', $reportData);

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_report_themselves()
    {
        $reportData = [
            'target_type' => 'user',
            'target_id' => $this->user->id,
            'reason' => 'fraud',
            'title' => 'Self report',
            'details' => 'Testing self-report prevention',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_id']);
    }

    /** @test */
    public function user_cannot_report_their_own_ad()
    {
        $myAd = Ad::factory()->create(['user_id' => $this->user->id]);

        $reportData = [
            'target_type' => 'ad',
            'target_id' => $myAd->id,
            'reason' => 'spam',
            'title' => 'My own ad',
            'details' => 'Testing self-report prevention',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_id']);
    }

    /** @test */
    public function user_cannot_create_duplicate_report_within_24_hours()
    {
        // Create first report
        Report::factory()->create([
            'reported_by_user_id' => $this->user->id,
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'reason' => 'spam',
        ]);

        // Attempt duplicate
        $reportData = [
            'target_type' => 'ad',
            'target_id' => $this->ad->id,
            'reason' => 'spam',
            'title' => 'Duplicate report',
            'details' => 'This should fail',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_id']);
    }

    /** @test */
    public function report_creation_requires_all_mandatory_fields()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_type', 'target_id', 'reason', 'title', 'details']);
    }

    /** @test */
    public function report_creation_validates_target_type()
    {
        $reportData = [
            'target_type' => 'invalid_type',
            'target_id' => 1,
            'reason' => 'spam',
            'title' => 'Test',
            'details' => 'Test details',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', $reportData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_type']);
    }

    /** @test */
    public function authenticated_user_can_view_their_own_reports()
    {
        Report::factory()->count(3)->create(['reported_by_user_id' => $this->user->id]);
        Report::factory()->count(2)->create(); // Other users' reports

        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/my-reports');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.reports.data');
    }

    /** @test */
    public function regular_user_cannot_access_admin_report_index()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/admin/index');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_admin_report_index()
    {
        Report::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/admin/index');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.reports.data');
    }

    /** @test */
    public function moderator_can_access_admin_report_index()
    {
        Report::factory()->count(5)->create();

        $response = $this->actingAs($this->moderator)->getJson('/api/v1/reports/admin/index');

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_view_their_own_report()
    {
        $report = Report::factory()->create(['reported_by_user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.report.id', $report->id);
    }

    /** @test */
    public function user_cannot_view_others_report()
    {
        $otherUser = User::factory()->create();
        $report = Report::factory()->create(['reported_by_user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_any_report()
    {
        $report = Report::factory()->create(['reported_by_user_id' => $this->user->id]);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_assign_report_to_moderator()
    {
        $report = Report::factory()->create();

        $response = $this->actingAs($this->admin)->postJson("/api/v1/reports/{$report->id}/assign", [
            'moderator_id' => $this->moderator->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'assigned_to' => $this->moderator->id,
            'status' => Report::STATUS_UNDER_REVIEW,
        ]);
    }

    /** @test */
    public function regular_user_cannot_assign_reports()
    {
        $report = Report::factory()->create();

        $response = $this->actingAs($this->user)->postJson("/api/v1/reports/{$report->id}/assign", [
            'moderator_id' => $this->moderator->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function assigned_moderator_can_view_report()
    {
        $report = Report::factory()->create(['assigned_to' => $this->moderator->id]);

        $response = $this->actingAs($this->moderator)->getJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function assigned_moderator_can_update_report_status()
    {
        $report = Report::factory()->create([
            'assigned_to' => $this->moderator->id,
            'status' => Report::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->actingAs($this->moderator)->putJson("/api/v1/reports/{$report->id}/status", [
            'status' => Report::STATUS_RESOLVED,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_RESOLVED,
        ]);
    }

    /** @test */
    public function regular_user_cannot_update_report_status()
    {
        $report = Report::factory()->create();

        $response = $this->actingAs($this->user)->putJson("/api/v1/reports/{$report->id}/status", [
            'status' => Report::STATUS_RESOLVED,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_resolve_report()
    {
        Notification::fake();

        $report = Report::factory()->create([
            'reported_by_user_id' => $this->user->id,
            'status' => Report::STATUS_UNDER_REVIEW,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/reports/{$report->id}/actions/resolve", [
            'admin_message' => 'Issue has been addressed.',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_RESOLVED,
        ]);

        // Verify notification sent to reporter
        Notification::assertSentTo($this->user, ReportResolvedNotification::class);
    }

    /** @test */
    public function admin_can_close_report()
    {
        Notification::fake();

        $report = Report::factory()->create([
            'reported_by_user_id' => $this->user->id,
            'status' => Report::STATUS_RESOLVED,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/reports/{$report->id}/actions/close", [
            'admin_message' => 'Closing this report.',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_CLOSED,
        ]);

        Notification::assertSentTo($this->user, ReportResolvedNotification::class);
    }

    /** @test */
    public function admin_can_delete_report()
    {
        $report = Report::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    /** @test */
    public function regular_user_cannot_delete_report()
    {
        $report = Report::factory()->create(['reported_by_user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function moderator_cannot_delete_report()
    {
        $report = Report::factory()->create();

        $response = $this->actingAs($this->moderator)->deleteJson("/api/v1/reports/{$report->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_filter_reports_by_status()
    {
        Report::factory()->count(2)->create(['status' => Report::STATUS_OPEN]);
        Report::factory()->count(3)->create(['status' => Report::STATUS_RESOLVED]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/admin/index?status=open');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.reports.data');
    }

    /** @test */
    public function admin_can_filter_reports_by_target_type()
    {
        Report::factory()->count(3)->targetingAd()->create();
        Report::factory()->count(2)->targetingUser()->create();

        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/admin/index?target_type=ad');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.reports.data');
    }

    /** @test */
    public function admin_can_filter_reports_by_assigned_moderator()
    {
        Report::factory()->count(2)->create(['assigned_to' => $this->moderator->id]);
        Report::factory()->count(3)->create(['assigned_to' => null]);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/reports/admin/index?assigned_to={$this->moderator->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.reports.data');
    }

    /** @test */
    public function rate_limiting_prevents_spam_reports()
    {
        // Make 10 successful report creations
        for ($i = 1; $i <= 10; $i++) {
            $ad = Ad::factory()->create();
            $response = $this->actingAs($this->user)->postJson('/api/v1/reports', [
                'target_type' => 'ad',
                'target_id' => $ad->id,
                'reason' => 'spam',
                'title' => "Report $i",
                'details' => "Details $i",
            ]);
            $response->assertStatus(201);
        }

        // 11th attempt should be rate limited
        $ad = Ad::factory()->create();
        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', [
            'target_type' => 'ad',
            'target_id' => $ad->id,
            'reason' => 'spam',
            'title' => 'Report 11',
            'details' => 'Should fail',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Too many report submissions. Please try again later.');
    }

    /** @test */
    public function report_list_respects_pagination_limits()
    {
        Report::factory()->count(60)->create();

        // Default limit should be 15
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/admin/index');
        $response->assertStatus(200)
            ->assertJsonCount(15, 'data.reports.data');

        // Custom limit
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/admin/index?limit=25');
        $response->assertJsonCount(25, 'data.reports.data');

        // Max limit should be 50
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/admin/index?limit=100');
        $response->assertJsonCount(50, 'data.reports.data');
    }
}
