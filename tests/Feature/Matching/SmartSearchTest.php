<?php

namespace Tests\Feature\Matching;

use App\Enums\ListingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class SmartSearchTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedMedicineData();
        config(['services.matching.enabled' => true]);
        Cache::flush();
    }

    private function names($response): array
    {
        return collect($response->viewData('listings')->items())->pluck('name')->all();
    }

    private function fakeMatch(array $ids, array $understood = []): void
    {
        Http::fake(['matching.test/match' => Http::response([
            'method' => 'semantic:e5', 'degraded' => false, 'excluded' => [],
            'query' => $understood + ['normalized' => '', 'brands' => ['napa'], 'generics' => ['paracetamol'], 'strengths' => [500], 'form' => 'tablet', 'corrections' => []],
            'results' => array_map(fn ($id) => ['id' => $id, 'score' => 0.9], $ids),
        ])]);
    }

    public function test_a_bangla_search_shows_the_services_results_and_what_it_understood(): void
    {
        $napa = $this->makeListing(brand: 'Napa');
        $ace = $this->makeListing(brand: 'Ace');
        $this->makeListing(medicine: $this->medicine('Omeprazole', '20 mg', 'Capsule'), brand: 'Seclo');
        $this->fakeMatch([$ace->id, $napa->id]);

        $response = $this->get('/medicines?q='.urlencode('নাপা ৫০০ ট্যাবলেট'))
            ->assertOk()
            ->assertSee('Paracetamol · 500 · Tablet');

        $this->assertSame(['Ace', 'Napa'], $this->names($response)); // the service's order; Seclo was not returned
    }

    public function test_only_listings_the_public_may_see_are_sent_to_the_service(): void
    {
        $visible = $this->makeListing(brand: 'Napa');
        $this->makeListing(status: ListingStatus::PendingReview, brand: 'Hidden1');
        $this->makeListing(status: ListingStatus::Blocked, brand: 'Hidden2');
        $suspendedOwner = User::factory()->suspended()->create();
        $this->makeListing($suspendedOwner, brand: 'Hidden3');
        $this->fakeMatch([$visible->id]);

        $this->get('/medicines?q=napa');

        Http::assertSent(fn (Request $r) => collect($r['candidates'])->pluck('id')->all() === [$visible->id]);
    }

    public function test_filters_limit_what_is_sent(): void
    {
        $dhaka = $this->makeListing(brand: 'Napa', district: 'Dhaka');
        $this->makeListing(brand: 'Ace', district: 'Sylhet');
        $this->fakeMatch([$dhaka->id]);

        $this->get('/medicines?q=napa&district_id='.$dhaka->district_id);

        Http::assertSent(fn (Request $r) => collect($r['candidates'])->pluck('id')->all() === [$dhaka->id]);
    }

    public function test_spelling_corrections_are_shown(): void
    {
        $napa = $this->makeListing(brand: 'Napa');
        $this->fakeMatch([$napa->id], ['corrections' => ['paracetemol' => 'paracetamol']]);

        $this->get('/medicines?q=paracetemol')->assertSee('“paracetemol” read as “paracetamol”', false);
    }

    public function test_keyword_search_is_used_when_the_service_is_down(): void
    {
        $this->makeListing(brand: 'Napa');
        Http::fake(['matching.test/*' => fn () => throw new ConnectionException('refused')]);

        $response = $this->get('/medicines?q=napa')
            ->assertOk()
            ->assertSee('Smart matching is unavailable right now');

        $this->assertSame(['Napa'], $this->names($response)); // keyword search still found it
    }

    public function test_browsing_without_a_search_phrase_does_not_call_the_service(): void
    {
        $this->makeListing(brand: 'Napa');
        Http::fake();

        $this->get('/medicines')->assertOk()->assertDontSee('Best match');
        Http::assertNothingSent();
    }

    public function test_the_request_form_preselects_the_medicine_the_service_understood(): void
    {
        $syrup = $this->medicine('Paracetamol', '120 mg / 5 ml', 'Syrup');
        Http::fake(['matching.test/normalize' => Http::response(['normalized' => 'paracetamol syrup', 'brands' => [], 'generics' => ['paracetamol'], 'strengths' => [], 'form' => 'syrup', 'corrections' => []])]);

        $this->actingAs(User::factory()->create())
            ->get('/requests/create?q='.urlencode('প্যারাসিটামল সিরাপ'))
            ->assertSee('value="'.$syrup->id.'" selected', false);
    }

    public function test_an_ambiguous_understanding_preselects_nothing(): void
    {
        Http::fake(['matching.test/normalize' => Http::response(['normalized' => 'paracetamol', 'brands' => [], 'generics' => ['paracetamol'], 'strengths' => [], 'form' => null, 'corrections' => []])]);

        $this->actingAs(User::factory()->create())->get('/requests/create?q=xyz')->assertDontSee('" selected', false);
    }
}
