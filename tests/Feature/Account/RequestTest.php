<?php

namespace Tests\Feature\Account;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Models\MedicineRequest;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
        $this->user = User::factory()->create($this->area('Dhaka'));
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'medicine_id' => $this->medicine()->id,
            'quantity' => 10,
            'unit' => 'tablets',
            'preference' => 'either',
            'notes' => '',
            'confirm' => '1',
        ], $this->area('Dhaka'), $overrides);
    }

    public function test_a_user_can_post_a_request(): void
    {
        $response = $this->actingAs($this->user)->post('/requests', $this->form(['notes' => 'Can collect on Friday']));

        $request = MedicineRequest::first();
        $response->assertRedirect(route('requests.show', $request));
        $this->assertSame($this->user->id, $request->user_id);
        $this->assertSame(RequestStatus::Active, $request->status);
        $this->assertTrue($request->expires_at->isAfter(now()->addDays(59)));
    }

    public function test_owner_and_status_cannot_be_set_from_the_form(): void
    {
        $this->actingAs($this->user)->post('/requests', $this->form(['user_id' => User::factory()->create()->id, 'status' => 'completed']));

        $request = MedicineRequest::first();
        $this->assertSame($this->user->id, $request->user_id);
        $this->assertSame(RequestStatus::Active, $request->status);
    }

    public function test_invalid_requests_are_rejected(): void
    {
        $this->actingAs($this->user);

        $this->post('/requests', $this->form(['medicine_id' => 99999]))->assertSessionHasErrors('medicine_id');
        $this->post('/requests', $this->form(['quantity' => 0]))->assertSessionHasErrors('quantity');
        $this->post('/requests', $this->form(['unit' => 'kg']))->assertSessionHasErrors('unit');
        $this->post('/requests', $this->form(['preference' => 'cheapest']))->assertSessionHasErrors('preference');
        $this->post('/requests', $this->form(['notes' => str_repeat('a', 501)]))->assertSessionHasErrors('notes');
        $this->post('/requests', $this->form(['confirm' => null]))->assertSessionHasErrors('confirm');
        $this->post('/requests', $this->form(['medicine_id' => $this->medicine('Diazepam', '5 mg', 'Tablet')->id]))->assertSessionHasErrors('medicine_id');

        $this->assertSame(0, MedicineRequest::count());
    }

    public function test_the_owner_can_edit_an_active_request(): void
    {
        $request = $this->makeRequest($this->user);

        $this->actingAs($this->user)->get("/requests/{$request->id}/edit")->assertOk();
        $this->put("/requests/{$request->id}", $this->form(['quantity' => 25]))->assertRedirect(route('requests.show', $request));
        $this->assertSame(25, $request->fresh()->quantity);
    }

    public function test_the_owner_can_cancel_a_request(): void
    {
        $request = $this->makeRequest($this->user);

        $this->actingAs($this->user)->patch("/requests/{$request->id}/cancel");

        $this->assertSame(RequestStatus::Cancelled, $request->fresh()->status);
        $this->assertNotNull($request->fresh()->closed_at);
        $this->put("/requests/{$request->id}", $this->form())->assertForbidden();
        $this->patch("/requests/{$request->id}/cancel")->assertForbidden();
    }

    public function test_users_cannot_see_or_change_someone_elses_request(): void
    {
        $request = $this->makeRequest(User::factory()->create());
        $this->actingAs($this->user);

        $this->get("/requests/{$request->id}")->assertForbidden();
        $this->get("/requests/{$request->id}/edit")->assertForbidden();
        $this->put("/requests/{$request->id}", $this->form(['quantity' => 1]))->assertForbidden();
        $this->patch("/requests/{$request->id}/cancel")->assertForbidden();
        $this->get('/requests')->assertDontSee(route('requests.show', $request), false);
    }

    public function test_the_request_page_shows_matching_listings(): void
    {
        $this->makeListing(brand: 'Napa');
        $request = $this->makeRequest($this->user);

        $this->actingAs($this->user)->get("/requests/{$request->id}")->assertOk()->assertSee('Matching listings')->assertSee('Napa');
    }

    public function test_matches_follow_the_identity_and_visibility_rules(): void
    {
        $request = $this->makeRequest($this->user, district: 'Sylhet');

        $near = $this->makeListing(brand: 'Ace', district: 'Sylhet');
        $far = $this->makeListing(brand: 'Napa', district: 'Dhaka');
        $requested = $this->makeListing(status: ListingStatus::Requested, brand: 'Napa', district: 'Rajshahi');
        $this->makeListing(medicine: $this->medicine('Paracetamol', '665 mg', 'Tablet'), brand: 'Napa Extend'); // other strength
        $this->makeListing(medicine: $this->medicine('Paracetamol', '120 mg / 5 ml', 'Syrup')); // other form
        $this->makeListing(status: ListingStatus::Reserved);
        $this->makeListing(status: ListingStatus::PendingReview);
        $this->makeListing(status: ListingStatus::Blocked);
        $this->makeListing(owner: $this->user); // my own listing

        $ids = app(MatchingService::class)->forRequest($request)->pluck('id')->all();

        $this->assertSame($near->id, $ids[0], 'The same district comes first.');
        $this->assertEqualsCanonicalizing([$near->id, $far->id, $requested->id], $ids);
    }

    public function test_donation_only_requests_see_only_donation_listings(): void
    {
        $request = $this->makeRequest($this->user, extra: ['preference' => 'donation']);
        $this->makeListing(brand: 'Napa', extra: ['type' => 'affordable_access']);

        $this->assertCount(0, app(MatchingService::class)->forRequest($request));
    }

    public function test_the_request_form_can_be_prefilled_from_a_search(): void
    {
        $this->actingAs($this->user)->get('/requests/create?q=napa')
            ->assertOk()
            ->assertSee('value="'.$this->medicine()->id.'" selected', false);

        $this->get('/requests/create?q[]=x')->assertOk();
    }

    public function test_expired_requests_are_closed_by_the_scheduled_command(): void
    {
        $old = $this->makeRequest($this->user);
        $old->expires_at = now()->subDay();
        $old->save();
        $fresh = $this->makeRequest($this->user);

        $this->artisan('requests:expire')->expectsOutputToContain('Expired 1 request')->assertSuccessful();

        $this->assertSame(RequestStatus::Expired, $old->fresh()->status);
        $this->assertSame(RequestStatus::Active, $fresh->fresh()->status);
    }
}
