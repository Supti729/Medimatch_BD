<?php

namespace Tests\Feature\Admin;

use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\User;
use App\Models\VerificationSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class VerificationWorkflowTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    private User $requester;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
        Storage::fake('local');
        Storage::fake('public');
        $this->requester = User::factory()->create();
        $this->reviewer = User::factory()->reviewer()->create(['name' => 'Dr Reviewer']);
    }

    private function upload($request): void
    {
        $this->actingAs($this->requester)->post('/verification', [
            'medicine_request_id' => $request->id,
            'document' => UploadedFile::fake()->image('prescription.jpg'),
            'consent' => '1',
        ]);
    }

    public function test_a_prescription_is_stored_privately_with_history(): void
    {
        $request = $this->makeRequest($this->requester, $this->medicine('Amlodipine', '5 mg', 'Tablet'));

        $this->upload($request);

        $submission = VerificationSubmission::firstOrFail();
        $this->assertSame(VerificationStatus::Submitted, $submission->status);
        Storage::disk('local')->assertExists($submission->document_path);
        Storage::disk('public')->assertMissing($submission->document_path);
        $this->assertCount(1, $submission->events);
        $this->actingAs($this->requester)->get('/verification')->assertSee('You uploaded a document.')->assertDontSee($submission->document_path);
    }

    public function test_uploads_are_validated(): void
    {
        $request = $this->makeRequest($this->requester, $this->medicine('Amlodipine', '5 mg', 'Tablet'));
        $this->actingAs($this->requester);

        $this->post('/verification', ['medicine_request_id' => $request->id, 'document' => UploadedFile::fake()->create('x.exe', 10), 'consent' => '1'])->assertSessionHasErrors('document');
        $this->post('/verification', ['medicine_request_id' => $request->id, 'document' => UploadedFile::fake()->image('p.jpg')->size(6000), 'consent' => '1'])->assertSessionHasErrors('document');
        $this->post('/verification', ['medicine_request_id' => $request->id, 'document' => UploadedFile::fake()->image('p.jpg')])->assertSessionHasErrors('consent');

        // Not needed for a non-prescription medicine
        $otc = $this->makeRequest($this->requester);
        $this->post('/verification', ['medicine_request_id' => $otc->id, 'document' => UploadedFile::fake()->image('p.jpg'), 'consent' => '1'])->assertSessionHasErrors('medicine_request_id');

        // Someone else's request
        $theirs = $this->makeRequest(User::factory()->create(), $this->medicine('Amlodipine', '5 mg', 'Tablet'));
        $this->post('/verification', ['medicine_request_id' => $theirs->id, 'document' => UploadedFile::fake()->image('p.jpg'), 'consent' => '1'])->assertNotFound();

        $this->assertSame(0, VerificationSubmission::count());
    }

    public function test_only_reviewers_and_admins_can_open_the_document_and_every_view_is_logged(): void
    {
        $this->upload($this->makeRequest($this->requester, $this->medicine('Amlodipine', '5 mg', 'Tablet')));
        $submission = VerificationSubmission::first();

        $this->actingAs($this->requester)->get("/admin/verifications/{$submission->id}/document")->assertForbidden();
        $this->actingAs(User::factory()->create())->get("/admin/verifications/{$submission->id}/document")->assertForbidden();

        $this->actingAs($this->reviewer)->get("/admin/verifications/{$submission->id}/document")
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertTrue(AuditLog::where('action', 'verification.document_viewed')->where('actor_id', $this->reviewer->id)->exists());
    }

    public function test_opening_a_submission_starts_the_review(): void
    {
        $this->upload($this->makeRequest($this->requester, $this->medicine('Amlodipine', '5 mg', 'Tablet')));
        $submission = VerificationSubmission::first();

        $this->actingAs($this->reviewer)->get("/admin/verifications/{$submission->id}")->assertOk();

        $this->assertSame(VerificationStatus::UnderReview, $submission->fresh()->status);
        $this->assertSame($this->reviewer->id, $submission->fresh()->reviewer_id);
    }

    public function test_verifying_unblocks_a_reserved_prescription_handover_and_tells_the_donor_without_the_document(): void
    {
        $amlodipine = $this->medicine('Amlodipine', '5 mg', 'Tablet');
        $donor = User::factory()->create();
        $listing = $this->makeListing($donor, medicine: $amlodipine, brand: 'Amdocal');
        $request = $this->makeRequest($this->requester, $amlodipine);
        $this->actingAs($this->requester)->post("/medicines/{$listing->id}/reserve");
        $reservation = Reservation::first();
        $this->actingAs($donor)->patch("/reservations/{$reservation->id}/accept");
        $this->assertSame(RequestStatus::VerificationPending, $request->fresh()->status);

        $this->upload($request);
        $submission = VerificationSubmission::first();
        $this->actingAs($this->reviewer)->post("/admin/verifications/{$submission->id}/decision", ['decision' => 'verified', 'note' => 'Valid prescription from a registered doctor.'])
            ->assertSessionHas('success');

        $this->assertSame(RequestStatus::Reserved, $request->fresh()->status);
        $this->assertStringContainsString('was accepted', $this->requester->notifications()->get()->pluck('data.title')->implode(' | '));
        $donorTitles = $donor->notifications()->get()->pluck('data.title')->implode(' | ');
        $this->assertStringContainsString('Prescription check passed', $donorTitles);
        $this->assertStringNotContainsString($submission->document_path, json_encode($donor->notifications()->get()->pluck('data')));

        // The handover can now be completed
        $this->actingAs($donor)->patch("/reservations/{$reservation->id}/complete")->assertSessionHas('success');
    }

    public function test_a_new_upload_is_possible_after_a_reupload_request(): void
    {
        $request = $this->makeRequest($this->requester, $this->medicine('Amlodipine', '5 mg', 'Tablet'));
        $this->upload($request);
        $this->upload($request); // a second one while waiting is refused
        $this->assertSame(1, VerificationSubmission::count());

        $submission = VerificationSubmission::first();
        $this->actingAs($this->reviewer)->post("/admin/verifications/{$submission->id}/decision", ['decision' => 'reupload_required', 'note' => 'The date is not readable.']);
        $this->actingAs($this->reviewer)->post("/admin/verifications/{$submission->id}/decision", ['decision' => 'verified', 'note' => 'Changed my mind.'])->assertSessionHasErrors('decision');

        $this->upload($request);
        $this->assertSame(2, VerificationSubmission::count());
    }

    public function test_reviewers_need_a_reason_and_regular_users_cannot_decide(): void
    {
        $this->upload($this->makeRequest($this->requester, $this->medicine('Amlodipine', '5 mg', 'Tablet')));
        $submission = VerificationSubmission::first();

        $this->actingAs($this->reviewer)->post("/admin/verifications/{$submission->id}/decision", ['decision' => 'verified', 'note' => ''])->assertSessionHasErrors('note');
        $this->actingAs($this->requester)->post("/admin/verifications/{$submission->id}/decision", ['decision' => 'verified', 'note' => 'Approving myself.'])->assertForbidden();

        $this->assertTrue($submission->fresh()->status->isWaiting());
    }
}
