<?php

namespace App\Services;

use App\Models\MedicineListing;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Talks to the AI matching service in ai-service/ (POST /match, POST /normalize, GET /health).
 *
 * The service is helpful but never required:
 *   - every method returns null when the service is disabled, down, slow or answers strangely,
 *     and the caller then uses keyword search instead;
 *   - after a failure the service is skipped for a minute, so searches do not keep waiting for it;
 *   - ids the service returns are checked against the listings that were sent: it can re-order
 *     listings, never add one.
 */
class MatchingServiceClient
{
    private const DOWN_KEY = 'matching-service:down';

    /**
     * Rank listings for a search phrase.
     *
     * @param  Collection<int, MedicineListing>  $listings  already filtered to what the user may see
     * @return array{ids: list<int>, scores: array<int, float>, understood: array, excluded: array}|null
     */
    public function match(string $query, Collection $listings, int $limit = 50): ?array
    {
        if ($listings->isEmpty() || ! $this->available()) {
            return null;
        }

        $sent = $listings->take(config('services.matching.max_candidates'));

        $body = $this->post('/match', [
            'query' => mb_substr($query, 0, 200),
            'limit' => min($limit, 50),
            'candidates' => $sent->map(fn (MedicineListing $l) => [
                'id' => $l->id,
                'brand_name' => $l->brand_name,
                'generic_name' => $l->medicine->generic_name,
                'strength' => $l->medicine->strength,
                'dosage_form' => $l->medicine->dosage_form,
            ])->values()->all(),
        ]);

        if (! is_array($body['results'] ?? null) || ! is_array($body['query'] ?? null)) {
            return $body === null ? null : $this->fail('Unexpected /match response shape');
        }

        // Only keep ids we actually sent, in the service's order, without duplicates.
        $allowed = $sent->pluck('id')->flip();
        $results = collect($body['results'])
            ->filter(fn ($r) => is_array($r) && is_int($r['id'] ?? null) && $allowed->has($r['id']))
            ->unique('id')
            ->values();

        return [
            'ids' => $results->pluck('id')->all(),
            'scores' => $results->mapWithKeys(fn ($r) => [$r['id'] => (float) ($r['score'] ?? 0)])->all(),
            'understood' => $body['query'],
            'excluded' => is_array($body['excluded'] ?? null) ? $body['excluded'] : [],
            'degraded' => (bool) ($body['degraded'] ?? false),
        ];
    }

    /**
     * How the service understands a phrase: brands, generic names, strengths, form, spelling fixes.
     */
    public function normalize(string $text): ?array
    {
        if (! $this->available()) {
            return null;
        }

        $body = $this->post('/normalize', ['text' => mb_substr($text, 0, 200)]);

        return is_array($body['generics'] ?? null) ? $body : null;
    }

    /** Status for the admin dashboard and `php artisan ai:status`. Never throws. */
    public function health(): array
    {
        if (! config('services.matching.enabled')) {
            return ['status' => 'disabled'];
        }

        try {
            $response = $this->http()->get('/health');

            return $response->successful()
                ? ['status' => 'online'] + (array) $response->json()
                : ['status' => 'error', 'http_status' => $response->status()];
        } catch (Throwable $e) {
            return ['status' => 'offline', 'error' => class_basename($e)];
        }
    }

    public function available(): bool
    {
        return config('services.matching.enabled') && ! Cache::has(self::DOWN_KEY);
    }

    private function post(string $path, array $payload): ?array
    {
        try {
            $response = $this->http()->post($path, $payload);
        } catch (ConnectionException $e) {
            return $this->fail("Cannot reach the matching service: {$e->getMessage()}");
        }

        if (! $response->successful()) {
            return $this->fail("Matching service answered {$response->status()} on {$path}");
        }

        $json = $response->json();

        return is_array($json) ? $json : $this->fail("Matching service sent a non-JSON answer on {$path}");
    }

    private function http(): PendingRequest
    {
        $request = Http::baseUrl(config('services.matching.url'))
            ->timeout(config('services.matching.timeout'))
            ->connectTimeout(min(2, config('services.matching.timeout')))
            ->acceptJson();

        return config('services.matching.key') ? $request->withHeaders(['X-Api-Key' => config('services.matching.key')]) : $request;
    }

    private function fail(string $reason): null
    {
        Log::warning($reason.'. Using keyword search for '.config('services.matching.retry_after').' seconds.');
        Cache::put(self::DOWN_KEY, true, now()->addSeconds(config('services.matching.retry_after')));

        return null;
    }
}
