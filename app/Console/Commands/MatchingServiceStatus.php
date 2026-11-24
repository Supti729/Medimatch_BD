<?php

namespace App\Console\Commands;

use App\Services\MatchingServiceClient;
use Illuminate\Console\Command;

/**
 * Check that Laravel can reach the AI matching service:  php artisan ai:status
 */
class MatchingServiceStatus extends Command
{
    protected $signature = 'ai:status';

    protected $description = 'Check the connection to the AI matching service';

    public function handle(MatchingServiceClient $client): int
    {
        $health = $client->health();
        $this->line('URL:    '.config('services.matching.url'));
        $this->line('Key:    '.(config('services.matching.key') ? 'set' : 'not set'));
        $this->line('Status: '.$health['status']);

        if ($health['status'] !== 'online') {
            $this->warn('Searches will use keyword matching until the service is reachable.');

            return self::FAILURE;
        }

        $this->line('Model:  '.($health['semantic_model'] ?? '?').($health['model_loaded'] ?? false ? ' (loaded)' : ' (not loaded yet)'));

        // A real request, to confirm the API key is accepted
        $check = $client->normalize('napa 500 tab');
        if ($check === null) {
            $this->error('The service is up but refused a request. Check that MATCHING_SERVICE_KEY equals MEDIMATCH_AI_API_KEY.');

            return self::FAILURE;
        }

        $this->info('OK: "napa 500 tab" was understood as '.implode(', ', $check['generics']).' '.implode('+', $check['strengths']).' '.$check['form']);

        return self::SUCCESS;
    }
}
