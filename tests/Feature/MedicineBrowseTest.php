<?php

namespace Tests\Feature;

use App\Support\SampleListings;
use Tests\TestCase;

/**
 * Browsing and searching listings (front-end part: sample data).
 */
class MedicineBrowseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_browse_page_is_labelled_as_sample_data(): void
    {
        $this->get('/medicines')->assertOk()->assertSee('Sample data');
    }

    public function test_search_by_brand_name(): void
    {
        $this->get('/medicines?q=seclo')
            ->assertSee('Omeprazole')
            ->assertDontSee('Fexofenadine');
    }

    public function test_search_by_generic_name_finds_different_brands(): void
    {
        $names = SampleListings::search(['q' => 'paracetamol'])->pluck('name');

        $this->assertTrue($names->contains('Napa'));
        $this->assertTrue($names->contains('Ace'));
        $this->assertFalse($names->contains('Seclo'));
    }

    public function test_search_ignores_spacing_in_strength(): void
    {
        $this->assertSame(
            SampleListings::search(['q' => 'napa 500 mg'])->pluck('id')->all(),
            SampleListings::search(['q' => 'napa 500mg'])->pluck('id')->all(),
        );
        $this->assertNotEmpty(SampleListings::search(['q' => 'napa 500mg']));
    }

    public function test_filters_by_division_district_type_and_form(): void
    {
        $this->assertTrue(SampleListings::search(['division' => 'Dhaka'])->every(fn ($l) => $l['division'] === 'Dhaka'));
        $this->assertTrue(SampleListings::search(['district' => 'Gazipur'])->every(fn ($l) => $l['district'] === 'Gazipur'));
        $this->assertTrue(SampleListings::search(['type' => 'affordable_access'])->every(fn ($l) => $l['type'] === 'affordable_access'));
        $this->assertTrue(SampleListings::search(['form' => 'Capsule'])->every(fn ($l) => $l['dosage_form'] === 'Capsule'));
        $this->assertTrue(SampleListings::search(['available' => true])->every(fn ($l) => $l['status'] === 'available'));
    }

    public function test_blocked_and_unreviewed_listings_are_never_returned(): void
    {
        $hidden = SampleListings::all()->whereNotIn('status', config('medimatch.public_listing_statuses'));
        $this->assertNotEmpty($hidden, 'The sample data should include hidden listings to test against.');

        // Not in search results, even when searched for by exact name...
        $this->assertEmpty(SampleListings::search(['q' => 'Napa Extend']));
        $this->get('/medicines?q=Napa+Extend')->assertSee('No listings match your search');

        // ...and not reachable by direct link.
        foreach ($hidden as $listing) {
            $this->get('/medicines/'.$listing['id'])->assertNotFound();
        }
    }

    public function test_sorting(): void
    {
        $byName = SampleListings::search(['sort' => 'name'])->pluck('name')->all();
        $sorted = $byName;
        natcasesort($sorted);
        $this->assertSame(array_values($sorted), $byName);

        $expiries = SampleListings::search(['sort' => 'expiry'])->pluck('expiry')->all();
        $this->assertSame(collect($expiries)->sortDesc()->values()->all(), $expiries);
    }

    public function test_results_are_paginated_and_keep_the_filters(): void
    {
        $this->get('/medicines')
            ->assertSee('Showing')
            ->assertSee('page=2', false);

        $this->get('/medicines?type=donation&page=2')
            ->assertOk()
            ->assertSee('type=donation', false);
    }

    public function test_list_view_can_be_selected(): void
    {
        $this->get('/medicines?view=list')->assertOk()->assertSee('name="view" value="list"', false);
    }

    public function test_empty_search_shows_a_helpful_empty_state(): void
    {
        $this->get('/medicines?q=zzzznotamedicine')
            ->assertOk()
            ->assertSee('No listings match your search')
            ->assertSee('Request this medicine');
    }

    public function test_unexpected_query_values_do_not_break_search(): void
    {
        $this->get('/medicines?division=Atlantis&sort=drop+table&type[]=x&q[]=y&page=abc&form=<script>')
            ->assertOk()
            ->assertSee('16')
            ->assertDontSee('<script>', false);
    }

    public function test_search_text_is_escaped_in_the_page(): void
    {
        $this->get('/medicines?q='.urlencode('<img src=x onerror=alert(1)>'))
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_a_district_from_another_division_is_ignored(): void
    {
        // Sylhet district does not belong to Dhaka division, so only the division filter applies.
        $this->get('/medicines?division=Dhaka&district=Sylhet')
            ->assertOk()
            ->assertDontSee('No listings match your search');
    }

    public function test_details_page_shows_listing_information_and_safety_notes(): void
    {
        $this->get('/medicines/10')
            ->assertOk()
            ->assertSee('Amdocal')
            ->assertSee('Amlodipine')
            ->assertSee('A prescription check is needed for this medicine')
            ->assertSee('Before you request')
            ->assertSee('Log in to request')
            ->assertSee('Sample data');
    }

    public function test_details_page_never_shows_contact_details(): void
    {
        $this->get('/medicines/1')
            ->assertSee('Phone number, email and address are private.')
            ->assertDontSee('mailto:', false)
            ->assertDontSee('tel:', false);
    }

    public function test_unavailable_listing_cannot_be_requested(): void
    {
        // Listing 6 is reserved in the sample data.
        $this->get('/medicines/6')
            ->assertOk()
            ->assertSee('Not available to request')
            ->assertDontSee('Log in to request');
    }

    public function test_unknown_or_non_numeric_listing_returns_not_found(): void
    {
        $this->get('/medicines/99999')->assertNotFound();
        $this->get('/medicines/abc')->assertNotFound();
    }
}
