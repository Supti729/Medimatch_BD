<?php

namespace App\Listeners;

use App\Enums\RequestStatus;
use App\Events\ListingPublished;
use App\Models\MedicineRequest;
use App\Notifications\MatchFoundNotification;

/**
 * When a listing goes live, tell people with an open request for the same medicine in the same division.
 * (Laravel discovers this listener automatically from its handle() type.)
 */
class NotifyMatchingRequesters
{
    public function handle(ListingPublished $event): void
    {
        $listing = $event->listing->loadMissing(['medicine', 'district']);

        MedicineRequest::query()
            ->with('owner')
            ->where('medicine_id', $listing->medicine_id)
            ->where('status', RequestStatus::Active->value)
            ->where('division_id', $listing->division_id)
            ->where('user_id', '!=', $listing->user_id)
            ->when($listing->type !== 'donation', fn ($q) => $q->where('preference', '!=', 'donation'))
            ->get()
            ->each(fn (MedicineRequest $request) => $request->owner->notify(new MatchFoundNotification($listing, $request)));
    }
}
