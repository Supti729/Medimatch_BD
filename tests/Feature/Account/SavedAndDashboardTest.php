<?php

namespace Tests\Feature\Account;

use App\Enums\ListingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class SavedAndDashboardTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
        $this->user = User::factory()->create(['name' => 'Nusrat Jahan']);
    }

    public function test_a_public_listing_can_be_saved_and_removed(): void
    {
        $listing = $this->makeListing(brand: 'Napa');
        $this->actingAs($this->user);

        $this->post("/saved/{$listing->id}")->assertSessionHas('success');
        $this->post("/saved/{$listing->id}"); // saving twice keeps one copy
        $this->assertSame(1, $this->user->savedListings()->count());
        $this->get('/saved')->assertSee('Napa');
        $this->get("/medicines/{$listing->id}")->assertSee('Saved');

        $this->delete("/saved/{$listing->id}");
        $this->assertSame(0, $this->user->savedListings()->count());
    }

    public function test_listings_that_are_not_public_cannot_be_saved(): void
    {
        $hidden = $this->makeListing(status: ListingStatus::PendingReview);

        $this->actingAs($this->user)->post("/saved/{$hidden->id}")->assertNotFound();
    }

    public function test_a_saved_listing_that_was_completed_is_not_shown_in_detail(): void
    {
        $listing = $this->makeListing(brand: 'Napa');
        $this->user->savedListings()->attach($listing->id);
        $listing->status = ListingStatus::Completed;
        $listing->save();

        $this->actingAs($this->user)->get('/saved')->assertSee('no longer available')->assertDontSee('>Napa <', false);
    }

    public function test_dashboard_counts_come_from_the_database(): void
    {
        $this->makeListing($this->user, ListingStatus::Available);
        $this->makeListing($this->user, ListingStatus::Requested);
        $this->makeListing($this->user, ListingStatus::Draft);
        $this->makeRequest($this->user);
        $this->makeListing(User::factory()->create(), ListingStatus::Available, brand: 'Ace');

        $response = $this->actingAs($this->user)->get('/dashboard')->assertOk()->assertSee('Welcome, Nusrat Jahan')->assertDontSee('Sample data');

        $stats = $response->viewData('stats');
        $this->assertSame(2, $stats['active_listings']);
        $this->assertSame(1, $stats['active_requests']);
        $this->assertSame(0, $stats['completed']);

        // The other user's listing matches my request
        $response->assertSee('Recommended matches')->assertSee('Ace');
    }

    public function test_a_new_user_sees_empty_states_not_sample_data(): void
    {
        $this->actingAs($this->user)->get('/dashboard')->assertSee('No matches yet')->assertSee('Nothing in progress')->assertDontSee('Preview User');
        $this->get('/requests')->assertSee('You have not requested any medicine yet');
        $this->get('/saved')->assertSee('No saved medicines');
        $this->get('/history')->assertSee('Nothing here yet');
    }
}
