<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\District;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

/**
 * Public browsing and search of real listings.
 */
class MedicineBrowseTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
    }

    public function test_browse_shows_public_listings_and_no_sample_notice(): void
    {
        $this->makeListing(brand: 'Napa');

        $this->get('/medicines')->assertOk()->assertSee('Napa')->assertDontSee('Sample data');
    }

    public function test_search_by_brand_generic_alias_and_strength(): void
    {
        $this->makeListing(brand: 'Napa');
        $this->makeListing(medicine: $this->medicine('Omeprazole', '20 mg', 'Capsule'), brand: 'Seclo');

        $this->get('/medicines?q=seclo')->assertSee('Seclo')->assertDontSee('>Napa <', false);
        $this->get('/medicines?q=omeprazole')->assertSee('Seclo');
        $this->get('/medicines?q=paracetamol')->assertSee('Napa')->assertDontSee('Seclo');
        // "Ace" is a verified alias of the same catalogue medicine as Napa
        $this->get('/medicines?q=ace')->assertSee('Napa');
        // Spacing in the strength does not matter
        $this->get('/medicines?q=napa+500mg')->assertSee('Napa');
        $this->get('/medicines?q=napa+500+mg')->assertSee('Napa');
    }

    public function test_listings_that_are_not_public_are_never_returned(): void
    {
        foreach ([ListingStatus::Draft, ListingStatus::PendingReview, ListingStatus::Rejected, ListingStatus::Blocked, ListingStatus::Cancelled, ListingStatus::Completed, ListingStatus::Paused] as $status) {
            $hidden = $this->makeListing(status: $status, brand: 'Hidden'.ucfirst($status->value));

            $this->get('/medicines?q=hidden')->assertDontSee('Hidden'.ucfirst($status->value));
            $this->get("/medicines/{$hidden->id}")->assertNotFound();
        }

        $visible = $this->makeListing(status: ListingStatus::Reserved, brand: 'Visible');
        $this->get("/medicines/{$visible->id}")->assertOk();
    }

    public function test_filters_by_location_type_form_strength_and_availability(): void
    {
        $this->makeListing(brand: 'Napa', district: 'Dhaka');
        $this->makeListing(brand: 'Ace', district: 'Sylhet', status: ListingStatus::Requested);
        $this->makeListing(medicine: $this->medicine('Omeprazole', '20 mg', 'Capsule'), brand: 'Seclo', district: 'Dhaka');

        $sylhet = District::where('name', 'Sylhet')->first();
        $this->get("/medicines?division_id={$sylhet->division_id}")->assertSee('Ace')->assertDontSee('Seclo');
        $this->get("/medicines?district_id={$sylhet->id}")->assertSee('Ace')->assertDontSee('Seclo');
        $this->get('/medicines?form=Capsule')->assertSee('Seclo')->assertDontSee('Ace');
        $this->get('/medicines?strength=20mg')->assertSee('Seclo')->assertDontSee('Ace');
        $this->get('/medicines?available=1')->assertSee('Napa')->assertDontSee('>Ace <', false);
        $this->get('/medicines?type=affordable_access')->assertSee('No listings match your search');
    }

    public function test_a_district_from_another_division_is_ignored(): void
    {
        $this->makeListing(brand: 'Napa', district: 'Dhaka');
        $dhaka = District::where('name', 'Dhaka')->first();
        $sylhet = District::where('name', 'Sylhet')->first();

        $this->get("/medicines?division_id={$dhaka->division_id}&district_id={$sylhet->id}")->assertOk()->assertSee('Napa');
    }

    public function test_sorting_and_pagination(): void
    {
        foreach (range(1, 11) as $i) {
            $this->makeListing(brand: 'Brand'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $this->get('/medicines?sort=name')->assertSeeInOrder(['Brand01', 'Brand02', 'Brand03'])->assertSee('page=2', false);
        $this->get('/medicines?sort=name&page=2')->assertSee('Brand10')->assertSee('Brand11')->assertSee('sort=name', false);
    }

    public function test_unexpected_query_values_do_not_break_search(): void
    {
        $this->makeListing(brand: 'Napa');

        $this->get('/medicines?division_id=abc&district_id[]=1&sort=drop+table&type[]=x&q[]=y&page=abc&form=<script>&strength[]=1')
            ->assertOk()
            ->assertSee('Napa')
            ->assertDontSee('<script>', false);
    }

    public function test_search_text_is_escaped_and_like_wildcards_are_literal(): void
    {
        $this->makeListing(brand: 'Napa');

        $this->get('/medicines?q='.urlencode('<img src=x onerror=alert(1)>'))->assertOk()->assertDontSee('<img src=x onerror=alert(1)>', false);
        $this->get('/medicines?q=%25')->assertSee('No listings match your search');
    }

    public function test_empty_search_offers_to_post_a_request_with_the_search_text(): void
    {
        $this->get('/medicines?q=zzzz')->assertOk()->assertSee('No listings match your search')->assertSee(route('requests.create', ['q' => 'zzzz']), false);
    }

    public function test_details_page_shows_the_listing_and_safety_notes_but_no_contact_details(): void
    {
        $donor = User::factory()->create(['name' => 'Rahim Uddin', 'phone' => '01755554444']);
        $listing = $this->makeListing($donor, medicine: $this->medicine('Amlodipine', '5 mg', 'Tablet'), brand: 'Amdocal');

        $this->get("/medicines/{$listing->id}")
            ->assertOk()
            ->assertSee('Amdocal')
            ->assertSee('Amlodipine')
            ->assertSee('A prescription check is needed for this medicine')
            ->assertSee('Log in to request')
            ->assertSee('Rahim Uddin')
            ->assertDontSee($donor->email)
            ->assertDontSee('01755554444');
    }

    public function test_reserved_listing_cannot_be_requested(): void
    {
        $listing = $this->makeListing(status: ListingStatus::Reserved);

        $this->actingAs(User::factory()->create())->get("/medicines/{$listing->id}")->assertSee('Not available to request');
    }

    public function test_unknown_or_non_numeric_listing_returns_not_found(): void
    {
        $this->get('/medicines/99999')->assertNotFound();
        $this->get('/medicines/abc')->assertNotFound();
    }

    public function test_home_page_shows_real_counts_and_latest_listings(): void
    {
        $this->makeListing(brand: 'Napa', district: 'Dhaka');
        $this->makeListing(brand: 'Ace', district: 'Sylhet');
        $this->makeListing(status: ListingStatus::Draft, brand: 'Secret');

        $this->get('/')->assertOk()->assertSee('Napa')->assertDontSee('Secret')->assertDontSee('Sample data')->assertDontSee('no figures are shown');
    }
}
