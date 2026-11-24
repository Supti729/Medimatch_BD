<?php

namespace Tests\Feature\Matching;

use App\Models\MedicineListing;
use App\Services\MatchingServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

class MatchingServiceClientTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMedicineData();
        config(['services.matching.enabled' => true]);
        Cache::flush();
    }

    private function listings()
    {
        $this->makeListing(brand: 'Napa');
        $this->makeListing(brand: 'Ace');

        return MedicineListing::with('medicine')->get();
    }

    private function understood(): array
    {
        return ['normalized' => 'napa 500', 'brands' => ['napa'], 'generics' => ['paracetamol'], 'strengths' => [500], 'form' => null, 'corrections' => []];
    }

    public function test_it_sends_the_key_and_the_listing_identity_and_keeps_the_services_order(): void
    {
        $listings = $this->listings();
        [$napa, $ace] = [$listings[0], $listings[1]];
        Http::fake(['matching.test/match' => Http::response(['method' => 'semantic:e5', 'degraded' => false, 'query' => $this->understood(), 'excluded' => [],
            'results' => [['id' => $ace->id, 'score' => 0.9], ['id' => $napa->id, 'score' => 0.8]]])]);

        $result = app(MatchingServiceClient::class)->match('napa 500', $listings);

        $this->assertSame([$ace->id, $napa->id], $result['ids']);
        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Api-Key', 'test-key')
            && $r['query'] === 'napa 500'
            && $r['candidates'][0]['generic_name'] === 'Paracetamol'
            && $r['candidates'][0]['strength'] === '500 mg'
            && ! array_key_exists('user_id', $r['candidates'][0]));
    }

    public function test_ids_it_did_not_send_and_duplicates_are_discarded(): void
    {
        $listings = $this->listings();
        Http::fake(['matching.test/match' => Http::response(['query' => $this->understood(), 'results' => [
            ['id' => 99999, 'score' => 1.0], ['id' => $listings[0]->id, 'score' => 0.9], ['id' => $listings[0]->id, 'score' => 0.8], ['id' => 'x'],
        ]])]);

        $this->assertSame([$listings[0]->id], app(MatchingServiceClient::class)->match('napa', $listings)['ids']);
    }

    public function test_failures_return_null_and_the_service_is_skipped_for_a_minute(): void
    {
        $listings = $this->listings();
        $attempts = 0;
        Http::fake(['matching.test/*' => function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('refused');
        }]);
        $client = app(MatchingServiceClient::class);

        $this->assertNull($client->match('napa', $listings));
        $this->assertNull($client->match('napa', $listings));
        $this->assertSame(1, $attempts, 'The second search must not wait for a service that just failed.');

        $this->travel(61)->seconds();
        $this->assertTrue($client->available());
    }

    public function test_error_responses_and_unexpected_bodies_return_null(): void
    {
        $listings = $this->listings();
        $client = app(MatchingServiceClient::class);

        Http::fake(['matching.test/match' => Http::response('Internal error', 500)]);
        $this->assertNull($client->match('napa', $listings));

        Cache::flush();
        Http::fake(['matching.test/match' => Http::response(['unexpected' => true])]);
        $this->assertNull($client->match('napa', $listings));
    }

    public function test_nothing_is_sent_when_the_service_is_switched_off(): void
    {
        config(['services.matching.enabled' => false]);
        Http::fake();

        $this->assertNull(app(MatchingServiceClient::class)->match('napa', $this->listings()));
        $this->assertSame('disabled', app(MatchingServiceClient::class)->health()['status']);
        Http::assertNothingSent();
    }
}
