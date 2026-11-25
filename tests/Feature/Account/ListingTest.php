<?php

namespace Tests\Feature\Account;

use App\Enums\ListingAction;
use App\Enums\ListingStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\District;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Models\User;
use Database\Seeders\LocationSeeder;
use Database\Seeders\MedicineCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([LocationSeeder::class, MedicineCatalogueSeeder::class]);
        Storage::fake('public');

        $this->owner = User::factory()->create();
    }

    private function medicine(string $generic, string $strength, string $form): Medicine
    {
        return Medicine::where(['generic_name' => $generic, 'strength' => $strength, 'dosage_form' => $form])->firstOrFail();
    }

    /** A valid listing form: Napa (Paracetamol 500 mg tablet) in Savar, Dhaka. */
    private function form(array $overrides = []): array
    {
        $district = District::where('name', 'Dhaka')->first();

        return array_merge([
            'medicine_id' => $this->medicine('Paracetamol', '500 mg', 'Tablet')->id,
            'brand_name' => 'napa',
            'quantity' => 20,
            'unit' => 'tablets',
            'expiry' => now()->addYear()->format('Y-m'),
            'batch_number' => 'bx-2291',
            'packaging' => 'sealed_box',
            'type' => 'donation',
            'division_id' => $district->division_id,
            'district_id' => $district->id,
            'upazila_id' => $district->upazilas()->where('name', 'Savar')->value('id'),
            'pickup_address' => 'House 12, Road 5, Bank Town, Savar',
            'confirm_sealed' => '1',
            'confirm_expiry' => '1',
            'confirm_storage' => '1',
            'confirm_allowed' => '1',
            'action' => 'submit',
        ], $overrides);
    }

    /** A listing owned by $user in the given status, created through the normal rules. */
    private function listing(ListingStatus $status = ListingStatus::Draft, ?User $user = null): MedicineListing
    {
        $data = $this->form();
        $listing = new MedicineListing([
            'medicine_id' => $data['medicine_id'], 'brand_name' => 'Napa', 'quantity' => 20, 'unit' => 'tablets',
            'expiry_date' => now()->addYear()->startOfMonth(), 'packaging' => 'sealed_box', 'type' => 'donation',
            'division_id' => $data['division_id'], 'district_id' => $data['district_id'], 'upazila_id' => $data['upazila_id'],
        ]);
        $listing->user_id = ($user ?? $this->owner)->id;
        $listing->status = $status;
        $listing->save();

        return $listing;
    }

    // ----- Creating -----

    public function test_create_page_offers_only_listable_catalogue_medicines(): void
    {
        $this->actingAs($this->owner)->get('/listings/create')
            ->assertOk()
            ->assertSee('Paracetamol 500 mg · Tablet')
            ->assertSee('data-brands="Ace|Napa"', false)
            ->assertDontSee('Diazepam')
            ->assertDontSee('Not connected yet');
    }

    public function test_a_user_can_submit_a_listing_for_review(): void
    {
        $this->actingAs($this->owner)->post('/listings', $this->form())->assertRedirect(route('listings.index', ['tab' => 'review']));

        $listing = MedicineListing::first();
        $this->assertSame($this->owner->id, $listing->user_id);
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNotNull($listing->submitted_at);
        $this->assertNull($listing->published_at, 'A listing must not be published before a moderator approves it.');
        $this->assertSame('BX-2291', $listing->batch_number);
        $this->assertSame('Napa', $listing->alias?->brand_name, 'The brand name should be linked to the catalogue alias.');
        $this->assertSame(now()->addYear()->startOfMonth()->toDateString(), $listing->expiry_date->toDateString());
    }

    public function test_a_listing_can_be_saved_as_a_draft(): void
    {
        $this->actingAs($this->owner)->post('/listings', $this->form(['action' => 'draft']));

        $this->assertSame(ListingStatus::Draft, MedicineListing::first()->status);
    }

    public function test_the_owner_and_status_cannot_be_set_from_the_form(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->owner)->post('/listings', $this->form([
            'user_id' => $other->id, 'status' => 'available', 'published_at' => now(), 'review_note' => 'approved',
        ]));

        $listing = MedicineListing::first();
        $this->assertSame($this->owner->id, $listing->user_id);
        $this->assertSame(ListingStatus::PendingReview, $listing->status);
        $this->assertNull($listing->published_at);
    }

    public function test_invalid_listing_data_is_rejected(): void
    {
        $this->actingAs($this->owner);

        $this->post('/listings', $this->form(['medicine_id' => 99999]))->assertSessionHasErrors('medicine_id');
        $this->post('/listings', $this->form(['quantity' => 0]))->assertSessionHasErrors('quantity');
        $this->post('/listings', $this->form(['quantity' => 5000]))->assertSessionHasErrors('quantity');
        $this->post('/listings', $this->form(['unit' => 'kilograms']))->assertSessionHasErrors('unit');
        $this->post('/listings', $this->form(['expiry' => 'soon']))->assertSessionHasErrors('expiry');
        $this->post('/listings', $this->form(['packaging' => 'opened']))->assertSessionHasErrors('packaging');
        $this->post('/listings', $this->form(['batch_number' => '<script>']))->assertSessionHasErrors('batch_number');
        $this->post('/listings', $this->form(['confirm_sealed' => null]))->assertSessionHasErrors('confirm_sealed');
        $this->post('/listings', $this->form(['district_id' => District::where('name', 'Sylhet')->value('id')]))->assertSessionHasErrors('district_id');

        $this->assertSame(0, MedicineListing::count());
    }

    // ----- Eligibility rules -----

    public function test_restricted_medicines_cannot_be_listed(): void
    {
        $insulin = $this->medicine('Insulin (all types)', 'Any', 'Injection');

        $this->actingAs($this->owner)->post('/listings', $this->form(['medicine_id' => $insulin->id]))
            ->assertSessionHasErrors(['medicine_id' => 'Insulin (all types) cannot be listed: Needs refrigeration (cold chain).']);

        $this->assertSame(0, MedicineListing::count());
    }

    public function test_medicines_close_to_expiry_are_rejected(): void
    {
        $this->actingAs($this->owner);

        $this->post('/listings', $this->form(['expiry' => now()->addMonth()->format('Y-m')]))->assertSessionHasErrors('expiry');
        $this->post('/listings', $this->form(['expiry' => now()->subMonth()->format('Y-m')]))->assertSessionHasErrors('expiry');
        $this->assertSame(0, MedicineListing::count());

        $this->post('/listings', $this->form(['expiry' => now()->addMonths(4)->format('Y-m')]))->assertSessionHasNoErrors();
    }

    public function test_any_user_can_create_an_affordable_access_listing(): void
    {
        $this->actingAs($this->owner)->post('/listings', $this->form(['type' => 'affordable_access']))->assertSessionHasNoErrors();
        $this->assertSame('affordable_access', MedicineListing::sole()->type);
    }

    public function test_affordable_access_is_refused_when_an_administrator_switches_it_off(): void
    {
        config(['medimatch.allow_affordable_access' => false]);

        $this->actingAs($this->owner)->get('/listings/create')->assertSee('Switched off by the administrators');
        $this->post('/listings', $this->form(['type' => 'affordable_access']))->assertSessionHasErrors('type');
        $this->assertSame(0, MedicineListing::count());
    }

    // ----- Pick-up address -----

    public function test_the_pickup_address_is_required_and_stored_encrypted(): void
    {
        $this->actingAs($this->owner);
        $this->post('/listings', $this->form(['pickup_address' => '']))->assertSessionHasErrors('pickup_address');
        $this->post('/listings', $this->form(['pickup_address' => 'Savar']))->assertSessionHasErrors('pickup_address');

        $this->post('/listings', $this->form())->assertSessionHasNoErrors();
        $listing = MedicineListing::sole();
        $this->assertSame('House 12, Road 5, Bank Town, Savar', $listing->pickup_address);

        $raw = \Illuminate\Support\Facades\DB::table('medicine_listings')->value('pickup_address');
        $this->assertStringNotContainsString('Bank Town', $raw);
        $this->assertArrayNotHasKey('pickup_address', $listing->toArray());

        $this->get(route('listings.edit', $listing))->assertSee('House 12, Road 5, Bank Town, Savar');
    }

    // ----- Form errors -----

    public function test_the_error_banner_lists_every_problem(): void
    {
        $this->actingAs($this->owner)->get('/listings/create');

        $this->followRedirects($this->from('/listings/create')->post('/listings', $this->form(['action' => null])))
            ->assertSee('Please check the form')
            ->assertSee('The action field is required.');
    }

    // ----- Photos -----

    public function test_photos_are_stored_and_limited(): void
    {
        $this->actingAs($this->owner);

        $this->post('/listings', $this->form(['images' => [UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf')]]))->assertSessionHasErrors('images.0');
        $this->post('/listings', $this->form(['images' => array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, 5))]))->assertSessionHasErrors('images');
        $this->assertSame(0, MedicineListing::count());

        $this->post('/listings', $this->form(['images' => [UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->image('expiry.png')]]));
        $listing = MedicineListing::first();
        $this->assertCount(2, $listing->images);
        Storage::disk('public')->assertExists($listing->images->first()->path);
    }

    public function test_photos_can_be_removed_when_editing(): void
    {
        $this->actingAs($this->owner)->post('/listings', $this->form(['action' => 'draft', 'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]]));
        $listing = MedicineListing::first();
        $first = $listing->images->first();

        $this->put("/listings/{$listing->id}", $this->form(['action' => 'draft', 'remove_images' => [$first->id]]));

        $this->assertCount(1, $listing->fresh()->images);
        Storage::disk('public')->assertMissing($first->path);
    }

    // ----- Editing and ownership -----

    public function test_the_owner_can_edit_a_draft(): void
    {
        $listing = $this->listing();

        $this->actingAs($this->owner)->get("/listings/{$listing->id}/edit")->assertOk()->assertSee('value="Napa"', false);
        $this->put("/listings/{$listing->id}", $this->form(['action' => 'draft', 'quantity' => 12]));

        $this->assertSame(12, $listing->fresh()->quantity);
        $this->assertSame(ListingStatus::Draft, $listing->fresh()->status);
    }

    public function test_editing_an_approved_listing_sends_it_back_to_review(): void
    {
        $listing = $this->listing(ListingStatus::Available);

        $this->actingAs($this->owner)->put("/listings/{$listing->id}", $this->form(['quantity' => 15]));

        $this->assertSame(ListingStatus::PendingReview, $listing->fresh()->status);
    }

    public function test_reserved_and_completed_listings_are_locked(): void
    {
        foreach ([ListingStatus::Reserved, ListingStatus::Completed, ListingStatus::PendingReview] as $status) {
            $listing = $this->listing($status);

            $this->actingAs($this->owner)->get("/listings/{$listing->id}/edit")->assertOk()->assertSee('cannot be edited');
            $this->put("/listings/{$listing->id}", $this->form(['quantity' => 1]))->assertForbidden();
            $this->assertSame(20, $listing->fresh()->quantity);
        }
    }

    public function test_users_cannot_view_edit_or_change_someone_elses_listing(): void
    {
        $listing = $this->listing(ListingStatus::Available);
        $intruder = User::factory()->create();

        $this->actingAs($intruder);
        $this->get("/listings/{$listing->id}/edit")->assertForbidden();
        $this->put("/listings/{$listing->id}", $this->form(['quantity' => 1]))->assertForbidden();
        $this->patch("/listings/{$listing->id}/status", ['action' => 'cancel'])->assertForbidden();
        $this->delete("/listings/{$listing->id}")->assertForbidden();

        $this->assertSame(20, $listing->fresh()->quantity);
        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);
    }

    public function test_my_listings_shows_only_my_own_listings(): void
    {
        $this->listing(ListingStatus::Draft);
        $theirs = $this->listing(ListingStatus::Draft, User::factory()->create());
        $theirs->brand_name = 'Someone Elses Brand';
        $theirs->save();

        $this->actingAs($this->owner)->get('/listings')->assertOk()->assertSee('Napa')->assertDontSee('Someone Elses Brand');
    }

    public function test_listing_tabs_filter_by_status(): void
    {
        $this->listing(ListingStatus::Available);
        $draft = $this->listing(ListingStatus::Draft);
        $draft->brand_name = 'Ace';
        $draft->save();

        $this->actingAs($this->owner);
        $this->get('/listings?tab=active')->assertSee('Napa')->assertDontSee('>Ace <', false);
        $this->get('/listings?tab=review')->assertSee('Ace');
        $this->get('/listings?tab=nonsense')->assertOk();
    }

    // ----- Status changes -----

    public function test_status_actions_follow_the_allowed_transitions(): void
    {
        $listing = $this->listing(ListingStatus::Available);
        $this->actingAs($this->owner);

        $this->patch("/listings/{$listing->id}/status", ['action' => 'pause'])->assertSessionHas('success');
        $this->assertSame(ListingStatus::Paused, $listing->fresh()->status);

        $this->patch("/listings/{$listing->id}/status", ['action' => 'reactivate']);
        $this->assertSame(ListingStatus::Available, $listing->fresh()->status);

        $this->patch("/listings/{$listing->id}/status", ['action' => 'mark_unavailable']);
        $this->assertSame(ListingStatus::Unavailable, $listing->fresh()->status);

        $this->patch("/listings/{$listing->id}/status", ['action' => 'cancel']);
        $this->assertSame(ListingStatus::Cancelled, $listing->fresh()->status);

        // Cancelled is final
        $this->patch("/listings/{$listing->id}/status", ['action' => 'reactivate'])->assertSessionHas('error');
        $this->assertSame(ListingStatus::Cancelled, $listing->fresh()->status);
    }

    public function test_owners_cannot_approve_their_own_listing(): void
    {
        $listing = $this->listing(ListingStatus::PendingReview);
        $this->actingAs($this->owner);

        // "Reactivate" would mean pending_review -> available, which only a moderator may do.
        $this->patch("/listings/{$listing->id}/status", ['action' => 'reactivate'])->assertSessionHas('error');
        $this->patch("/listings/{$listing->id}/status", ['action' => 'approve'])->assertSessionHasErrors('action');
        $this->assertSame(ListingStatus::PendingReview, $listing->fresh()->status);

        $this->patch("/listings/{$listing->id}/status", ['action' => 'withdraw']);
        $this->assertSame(ListingStatus::Draft, $listing->fresh()->status);
    }

    public function test_the_model_refuses_transitions_that_are_not_allowed(): void
    {
        $listing = $this->listing(ListingStatus::Completed);

        $this->expectException(InvalidStatusTransition::class);
        $listing->transitionTo(ListingStatus::Available);
    }

    public function test_drafts_can_be_deleted_but_published_listings_cannot(): void
    {
        $draft = $this->listing(ListingStatus::Draft);
        $live = $this->listing(ListingStatus::Available);
        $this->actingAs($this->owner);

        $this->delete("/listings/{$draft->id}")->assertRedirect(route('listings.index'));
        $this->assertModelMissing($draft);

        $this->delete("/listings/{$live->id}")->assertForbidden();
        $this->assertModelExists($live);
    }

    public function test_only_public_statuses_are_publicly_visible(): void
    {
        foreach (ListingStatus::cases() as $status) {
            $this->listing($status);
        }

        $visible = MedicineListing::publiclyVisible()->pluck('status')->map->value->sort()->values()->all();
        $this->assertSame(['available', 'requested', 'reserved'], $visible);
    }

    public function test_owner_actions_never_include_approving_or_unblocking(): void
    {
        $ids = fn (ListingStatus $status) => array_map(fn ($a) => $a->value, ListingAction::availableFor($status));

        $this->assertSame(['withdraw'], $ids(ListingStatus::PendingReview));
        $this->assertSame([], $ids(ListingStatus::Blocked));
        $this->assertSame([], $ids(ListingStatus::Completed));
        $this->assertSame([], $ids(ListingStatus::Cancelled));

        foreach (ListingStatus::cases() as $status) {
            foreach (ListingAction::availableFor($status) as $action) {
                $this->assertTrue($status->canTransitionTo($action->target()), "{$action->value} from {$status->value}");
            }
        }
    }
}
