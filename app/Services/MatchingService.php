<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use Illuminate\Database\Eloquent\Collection;

/**
 * Finds listings that can fulfil a request.
 *
 * Fixed safety rules come first and are never relaxed:
 *   - the SAME catalogue medicine (generic name + strength + dosage form)
 *   - the listing is public and can still be requested (available or requested)
 *   - not the requester's own listing, and not from a suspended account
 *   - "donation only" requests only see donation listings
 * Results are then ordered by distance: same district, then same division, then the rest.
 *
 * Part 3 (AI) may re-order these results by text similarity, but never adds listings that fail the rules above.
 */
class MatchingService
{
    public function forRequest(MedicineRequest $request, ?int $limit = null): Collection
    {
        return MedicineListing::query()
            ->with(MedicineListing::CARD_RELATIONS)
            ->where('medicine_id', $request->medicine_id)
            ->whereIn('status', [ListingStatus::Available->value, ListingStatus::Requested->value])
            ->where('user_id', '!=', $request->user_id)
            ->whereHas('owner', fn ($q) => $q->where('status', 'active'))
            ->when($request->preference === 'donation', fn ($q) => $q->where('type', 'donation'))
            ->orderByRaw('CASE WHEN district_id = ? THEN 0 WHEN division_id = ? THEN 1 ELSE 2 END', [$request->district_id, $request->division_id])
            ->orderByDesc('expiry_date')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }
}
