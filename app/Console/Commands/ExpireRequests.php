<?php

namespace App\Console\Commands;

use App\Enums\RequestStatus;
use App\Models\MedicineRequest;
use Illuminate\Console\Command;

/**
 * Closes open requests that passed their expiry date without being reserved.
 * Scheduled daily in routes/console.php. Run by hand with: php artisan requests:expire
 */
class ExpireRequests extends Command
{
    protected $signature = 'requests:expire';

    protected $description = 'Mark active medicine requests past their expiry date as expired';

    public function handle(): int
    {
        $expired = 0;

        MedicineRequest::where('status', RequestStatus::Active->value)
            ->where('expires_at', '<', now())
            ->each(function (MedicineRequest $request) use (&$expired) {
                // A request with a pending reservation is still in progress; leave it open.
                if ($request->reservations()->active()->exists()) {
                    return;
                }
                $expired += (int) $request->moveTo(RequestStatus::Expired);
            });

        $this->info("Expired {$expired} ".str('request')->plural($expired).'.');

        return self::SUCCESS;
    }
}
