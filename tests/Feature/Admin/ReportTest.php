<?php

namespace Tests\Feature\Admin;

use App\Enums\ReportStatus;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    private User $reporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
        $this->reporter = User::factory()->create();
    }

    public function test_a_user_can_report_a_public_listing_once(): void
    {
        $listing = $this->makeListing();

        $this->actingAs($this->reporter)->post('/reports', ['target_type' => 'listing', 'target_id' => $listing->id, 'reason' => 'expired', 'details' => 'The photo shows 2024.'])
            ->assertSessionHas('success');
        $this->post('/reports', ['target_type' => 'listing', 'target_id' => $listing->id, 'reason' => 'expired']);

        $this->assertSame(1, Report::count());
        $report = Report::first();
        $this->assertSame($listing->user_id, $report->reported_user_id);
        $this->assertSame(ReportStatus::Open, $report->status);
    }

    public function test_reports_are_validated_and_hidden_targets_cannot_be_reported(): void
    {
        $listing = $this->makeListing();
        $hidden = $this->makeListing(status: \App\Enums\ListingStatus::PendingReview);
        $this->actingAs($this->reporter);

        $this->post('/reports', ['target_type' => 'listing', 'target_id' => $listing->id, 'reason' => 'i-dont-like-it'])->assertSessionHasErrors('reason');
        $this->post('/reports', ['target_type' => 'user', 'target_id' => 1, 'reason' => 'other'])->assertSessionHasErrors('target_type');
        $this->post('/reports', ['target_type' => 'listing', 'target_id' => $hidden->id, 'reason' => 'other'])->assertNotFound();
        $this->assertSame(0, Report::count());
    }

    public function test_only_participants_can_report_a_conversation(): void
    {
        $donor = User::factory()->create();
        $listing = $this->makeListing($donor);
        $this->actingAs($this->reporter)->post("/medicines/{$listing->id}/reserve", ['message' => 'Hello']);
        $conversation = Conversation::first();

        $this->actingAs(User::factory()->create())->post('/reports', ['target_type' => 'conversation', 'target_id' => $conversation->id, 'reason' => 'harassment'])->assertNotFound();

        $this->actingAs($this->reporter)->post('/reports', ['target_type' => 'conversation', 'target_id' => $conversation->id, 'reason' => 'contact_details']);
        $this->assertSame($donor->id, Report::first()->reported_user_id);
    }

    public function test_admins_review_evidence_and_the_reporter_hears_the_outcome(): void
    {
        $donor = User::factory()->create();
        $listing = $this->makeListing($donor);
        $this->actingAs($this->reporter)->post("/medicines/{$listing->id}/reserve", ['message' => 'Hello, can I collect today?']);
        $conversation = Conversation::first();
        $this->post('/reports', ['target_type' => 'conversation', 'target_id' => $conversation->id, 'reason' => 'selling']);
        $report = Report::first();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get("/admin/reports/{$report->id}")->assertOk()->assertSee('Hello, can I collect today?');
        $this->assertTrue(AuditLog::where('action', 'report.evidence_viewed')->exists(), 'Reading a reported conversation must be logged.');

        $this->post("/admin/reports/{$report->id}/decision", ['decision' => 'resolved', 'note' => 'Warned the donor.'])->assertSessionHas('success');
        $this->assertSame(ReportStatus::Resolved, $report->fresh()->status);
        $this->assertSame('Your report was reviewed', $this->reporter->notifications()->latest()->first()->data['title']);

        $this->post("/admin/reports/{$report->id}/decision", ['decision' => 'rejected', 'note' => 'Again?'])->assertSessionHasErrors('decision');
    }

    public function test_regular_users_cannot_see_reports(): void
    {
        $this->actingAs($this->reporter)->get('/admin/reports')->assertForbidden();
    }
}
