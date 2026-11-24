<?php

namespace Tests\Feature\Account;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    private User $donor;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
        $this->donor = User::factory()->create();
        $this->requester = User::factory()->create();
    }

    private function reserve($listing, ?User $who = null)
    {
        return $this->actingAs($who ?? $this->requester)->post("/medicines/{$listing->id}/reserve", ['message' => 'Can collect on Friday']);
    }

    public function test_requesting_a_listing_marks_it_requested_and_links_my_open_request(): void
    {
        $listing = $this->makeListing($this->donor);
        $myRequest = $this->makeRequest($this->requester);

        $this->reserve($listing)->assertSessionHas('success');

        $reservation = Reservation::first();
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertSame($myRequest->id, $reservation->medicine_request_id);
        $this->assertSame('Can collect on Friday', $reservation->message);
        $this->assertSame(ListingStatus::Requested, $listing->fresh()->status);
    }

    public function test_cannot_request_own_listing_twice_or_one_that_is_not_available(): void
    {
        $own = $this->makeListing($this->donor);
        $this->reserve($own, $this->donor)->assertSessionHasErrors('reservation');

        $listing = $this->makeListing($this->donor);
        $this->reserve($listing);
        $this->reserve($listing)->assertSessionHasErrors('reservation');

        $reserved = $this->makeListing($this->donor, ListingStatus::Reserved);
        $this->reserve($reserved)->assertSessionHasErrors('reservation');

        $hidden = $this->makeListing($this->donor, ListingStatus::PendingReview);
        $this->reserve($hidden)->assertSessionHasErrors('reservation');

        $this->assertSame(1, Reservation::count());
    }

    public function test_prescription_medicines_need_an_open_request_first(): void
    {
        $listing = $this->makeListing($this->donor, medicine: $this->medicine('Amlodipine', '5 mg', 'Tablet'), brand: 'Amdocal');

        $this->reserve($listing)->assertSessionHasErrors('reservation');
        $this->assertSame(0, Reservation::count());

        $this->makeRequest($this->requester, $this->medicine('Amlodipine', '5 mg', 'Tablet'));
        $this->reserve($listing)->assertSessionHasNoErrors();
    }

    public function test_the_donor_accepts_one_requester_and_the_others_are_declined(): void
    {
        $listing = $this->makeListing($this->donor);
        $request = $this->makeRequest($this->requester);
        $other = User::factory()->create();
        $this->reserve($listing);
        $this->reserve($listing, $other);
        [$mine, $theirs] = Reservation::orderBy('id')->get()->all();

        $this->actingAs($this->donor)->patch("/reservations/{$mine->id}/accept")->assertSessionHas('success');

        $this->assertSame(ReservationStatus::Accepted, $mine->fresh()->status);
        $this->assertSame(ReservationStatus::Declined, $theirs->fresh()->status);
        $this->assertSame(ListingStatus::Reserved, $listing->fresh()->status);
        $this->assertSame(RequestStatus::Reserved, $request->fresh()->status);
    }

    public function test_only_the_donor_can_accept_or_decline(): void
    {
        $listing = $this->makeListing($this->donor);
        $this->reserve($listing);
        $reservation = Reservation::first();

        $this->actingAs($this->requester)->patch("/reservations/{$reservation->id}/accept")->assertForbidden();
        $this->actingAs(User::factory()->create())->patch("/reservations/{$reservation->id}/decline")->assertForbidden();
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_declining_the_last_request_makes_the_listing_available_again(): void
    {
        $listing = $this->makeListing($this->donor);
        $this->reserve($listing);

        $this->actingAs($this->donor)->patch('/reservations/'.Reservation::first()->id.'/decline');

        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
    }

    public function test_releasing_an_accepted_reservation_frees_the_listing_and_reopens_the_request(): void
    {
        $listing = $this->makeListing($this->donor);
        $request = $this->makeRequest($this->requester);
        $this->reserve($listing);
        $reservation = Reservation::first();
        $this->actingAs($this->donor)->patch("/reservations/{$reservation->id}/accept");

        $this->actingAs($this->donor)->patch("/reservations/{$reservation->id}/cancel");

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
        $this->assertSame($this->donor->id, $reservation->fresh()->cancelled_by);
        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
        $this->assertSame(RequestStatus::Active, $request->fresh()->status);
    }

    public function test_completing_a_handover_closes_the_listing_and_the_request(): void
    {
        $listing = $this->makeListing($this->donor);
        $request = $this->makeRequest($this->requester);
        $this->reserve($listing);
        $reservation = Reservation::first();

        // Cannot complete before it is accepted
        $this->actingAs($this->requester)->patch("/reservations/{$reservation->id}/complete")->assertSessionHasErrors('reservation');

        $this->actingAs($this->donor)->patch("/reservations/{$reservation->id}/accept");
        $this->actingAs($this->requester)->patch("/reservations/{$reservation->id}/complete")->assertSessionHas('success');

        $this->assertSame(ReservationStatus::Completed, $reservation->fresh()->status);
        $this->assertSame(ListingStatus::Completed, $listing->fresh()->status);
        $this->assertSame(RequestStatus::Completed, $request->fresh()->status);
        $this->assertSame(1, $this->donor->completedContributions());

        // A stranger cannot touch it
        $this->actingAs(User::factory()->create())->patch("/reservations/{$reservation->id}/cancel")->assertForbidden();
    }

    public function test_prescription_handovers_wait_for_the_prescription_check(): void
    {
        $amlodipine = $this->medicine('Amlodipine', '5 mg', 'Tablet');
        $listing = $this->makeListing($this->donor, medicine: $amlodipine, brand: 'Amdocal');
        $request = $this->makeRequest($this->requester, $amlodipine);
        $this->reserve($listing);
        $reservation = Reservation::first();

        $this->actingAs($this->donor)->patch("/reservations/{$reservation->id}/accept");
        $this->assertSame(RequestStatus::VerificationPending, $request->fresh()->status);

        $this->actingAs($this->donor)->patch("/reservations/{$reservation->id}/complete")->assertSessionHasErrors('reservation');
        $this->assertSame(ListingStatus::Reserved, $listing->fresh()->status);
    }

    public function test_pausing_or_cancelling_a_listing_declines_everyone_waiting(): void
    {
        $listing = $this->makeListing($this->donor);
        $request = $this->makeRequest($this->requester);
        $this->reserve($listing);

        $this->actingAs($this->donor)->patch("/listings/{$listing->id}/status", ['action' => 'pause']);

        $this->assertSame(ListingStatus::Paused, $listing->fresh()->status);
        $this->assertSame(ReservationStatus::Declined, Reservation::first()->status);
        $this->assertSame(RequestStatus::Active, $request->fresh()->status);
    }

    public function test_cancelling_a_request_releases_its_reservation(): void
    {
        $listing = $this->makeListing($this->donor);
        $request = $this->makeRequest($this->requester);
        $this->reserve($listing);
        $this->actingAs($this->donor)->patch('/reservations/'.Reservation::first()->id.'/accept');

        $this->actingAs($this->requester)->patch("/requests/{$request->id}/cancel");

        $this->assertSame(RequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
    }

    public function test_the_donor_sees_requests_on_the_listing_page_and_the_dashboard(): void
    {
        $listing = $this->makeListing($this->donor);
        $this->reserve($listing);

        $this->actingAs($this->donor)->get("/listings/{$listing->id}/edit")->assertSee('Requests for this listing')->assertSee('Can collect on Friday')->assertSee('Accept');
        $this->get('/dashboard')->assertSee('Needs your answer');
        $this->actingAs($this->requester)->get('/dashboard')->assertSee('Waiting for donor');
    }

    public function test_history_lists_finished_handovers_for_both_sides(): void
    {
        $listing = $this->makeListing($this->donor, brand: 'Napa');
        $this->makeRequest($this->requester);
        $this->reserve($listing);
        $reservation = Reservation::first();
        $this->actingAs($this->donor)->patch("/reservations/{$reservation->id}/accept");
        $this->patch("/reservations/{$reservation->id}/complete");

        $this->actingAs($this->donor)->get('/history?role=donated')->assertSee('Napa')->assertSee($this->requester->name);
        $this->actingAs($this->requester)->get('/history?role=received')->assertSee('Napa');
        $this->actingAs(User::factory()->create())->get('/history')->assertDontSee('Napa 500 mg');
    }

    public function test_a_suspended_donors_listing_cannot_be_requested_even_by_posting_its_id(): void
    {
        $listing = $this->makeListing($this->donor);
        $this->donor->status = \App\Enums\AccountStatus::Suspended;
        $this->donor->save();

        $this->reserve($listing)->assertSessionHasErrors('reservation');
        $this->assertSame(0, Reservation::count());
    }
}
