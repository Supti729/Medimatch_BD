<?php

namespace App\Events;

use App\Models\MedicineListing;
use Illuminate\Foundation\Events\Dispatchable;

/** A listing became public for the first time (approved by a moderator). */
class ListingPublished
{
    use Dispatchable;

    public function __construct(public readonly MedicineListing $listing) {}
}
