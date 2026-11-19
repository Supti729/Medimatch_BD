<?php

namespace App\Notifications;

use App\Models\MedicineListing;
use App\Models\MedicineRequest;

class MatchFoundNotification extends AppNotification
{
    public function __construct(public readonly MedicineListing $listing, public readonly MedicineRequest $request) {}

    protected function type(): string
    {
        return 'match';
    }

    protected function title(): string
    {
        return 'A match was found for your request';
    }

    protected function text(): string
    {
        $m = $this->listing->medicine;

        return "{$this->listing->brand_name} {$m->strength} in {$this->listing->district->name} matches your request for {$m->generic_name} {$m->strength}.";
    }

    protected function url(): string
    {
        return route('requests.show', $this->request->id, false);
    }
}
