<?php

namespace Tests\Feature\Matching;

use App\Models\MedicineListing;
use App\Services\MatchingServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\CreatesMedicineData;
use Tests\TestCase;

/**
 * Talks to the REAL AI service. Skipped unless you start the service and ask for it:
 *
 *   cd ai-service && .venv/bin/uvicorn app.main:app --port 8001          (in one terminal)
 *   MATCHING_E2E=1 php artisan test --group=e2e                          (in another)
 *
 * MATCHING_E2E_URL and MATCHING_E2E_KEY can point it at another service.
 */
#[Group('e2e')]
class EndToEndTest extends TestCase
{
    use CreatesMedicineData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! getenv('MATCHING_E2E')) {
            $this->markTestSkipped('Set MATCHING_E2E=1 and start ai-service to run end-to-end tests.');
        }

        Http::allowStrayRequests();
        Cache::flush();
        config([
            'services.matching.enabled' => true,
            'services.matching.url' => getenv('MATCHING_E2E_URL') ?: 'http://127.0.0.1:8001',
            'services.matching.key' => getenv('MATCHING_E2E_KEY') ?: null,
            'services.matching.timeout' => 10,
        ]);
        $this->withoutVite();
        $this->seedMedicineData();
    }

    public function test_the_real_service_ranks_real_listings_and_applies_the_identity_rules(): void
    {
        $napa = $this->makeListing(brand: 'Napa');
        $extra = $this->makeListing(medicine: $this->medicine('Paracetamol + Caffeine', '500 mg + 65 mg', 'Tablet'), brand: 'Napa Extra');
        $extend = $this->makeListing(medicine: $this->medicine('Paracetamol', '665 mg', 'Tablet'), brand: 'Napa Extend');
        $seclo = $this->makeListing(medicine: $this->medicine('Omeprazole', '20 mg', 'Capsule'), brand: 'Seclo');

        $result = app(MatchingServiceClient::class)->match('নাপা ৫০০ ট্যাবলেট', MedicineListing::with('medicine')->get());

        $this->assertNotNull($result, 'The service did not answer. Is it running, and is the key right?');
        $this->assertSame([$napa->id], $result['ids']);
        $this->assertSame(['paracetamol'], $result['understood']['generics']);
        $this->assertNotContains($extra->id, $result['ids']);
        $this->assertNotContains($extend->id, $result['ids']);
        $this->assertNotContains($seclo->id, $result['ids']);
    }

    public function test_the_browse_page_uses_the_real_service(): void
    {
        $this->makeListing(medicine: $this->medicine('Salbutamol', '100 mcg', 'Inhaler'), brand: 'Azmasol');
        $this->makeListing(brand: 'Napa');

        $response = $this->get('/medicines?q=ventolin+inhaler')->assertOk()->assertDontSee('Smart matching is unavailable');

        $this->assertSame('smart', $response->viewData('searchMode'));
        $this->assertSame(['Azmasol'], collect($response->viewData('listings')->items())->pluck('name')->all());
    }
}
