<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Division;
use App\Models\MedicineListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public browsing and search of medicine listings.
 *
 * Only publicly visible listings are ever returned (available, requested, reserved).
 * Drafts, listings waiting for review, rejected, blocked, cancelled and completed listings never appear.
 */
class MedicineController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): View
    {
        $filters = $this->cleanFilters($request);

        $listings = $this->search($filters)
            ->with(MedicineListing::CARD_RELATIONS)
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (MedicineListing $listing) => $listing->toCard());

        return view('medicines.index', [
            'listings' => $listings,
            'filters' => $filters,
            'view' => $request->query('view') === 'list' ? 'list' : 'grid',
            'activeFilterCount' => count(array_filter(array_diff_key($filters, ['q' => 1, 'sort' => 1]))),
            'usingSampleData' => false,
        ]);
    }

    public function show(Request $request, int $listing): View
    {
        $found = MedicineListing::publiclyVisible()->with(MedicineListing::CARD_RELATIONS)->find($listing);

        // Unknown, blocked and unreviewed listings all look the same to visitors: not found.
        abort_if($found === null, 404);

        $user = $request->user();

        return view('medicines.show', [
            'listing' => $found->toCard(),
            'model' => $found,
            'similar' => MedicineListing::publiclyVisible()
                ->with(MedicineListing::CARD_RELATIONS)
                ->where('medicine_id', $found->medicine_id)
                ->whereKeyNot($found->id)
                ->latest('published_at')
                ->limit(3)
                ->get()
                ->map->toCard(),
            'isOwner' => $user?->id === $found->user_id,
            'myReservation' => $user ? $found->reservations()->active()->where('requester_id', $user->id)->first() : null,
            'isSaved' => $user ? $user->savedListings()->whereKey($found->id)->exists() : false,
            'usingSampleData' => false,
        ]);
    }

    /**
     * Build the search query. Every word typed must match a brand name, a verified alias,
     * the generic name or the strength ("napa 500mg" finds "Napa 500 mg").
     */
    private function search(array $filters): Builder
    {
        $query = MedicineListing::publiclyVisible()->select('medicine_listings.*');

        foreach (Str::of($filters['q'])->lower()->squish()->explode(' ')->filter() as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $compact = '%'.addcslashes(preg_replace('/(\d)\s*(mg|mcg|ml|g)\b/', '$1 $2', $word), '%_\\').'%';

            $query->where(fn (Builder $w) => $w
                ->where('brand_name', 'like', $like)
                ->orWhereHas('medicine', fn (Builder $m) => $m
                    ->where('generic_name', 'like', $like)
                    ->orWhere('strength', 'like', $compact)
                    ->orWhere('dosage_form', 'like', $like)
                    ->orWhereHas('aliases', fn (Builder $a) => $a->where('brand_name', 'like', $like))));
        }

        $query
            ->when($filters['division_id'], fn ($q, $id) => $q->where('division_id', $id))
            ->when($filters['district_id'], fn ($q, $id) => $q->where('district_id', $id))
            ->when($filters['type'], fn ($q, $type) => $q->where('type', $type))
            ->when($filters['form'], fn ($q, $form) => $q->whereHas('medicine', fn ($m) => $m->where('dosage_form', $form)))
            ->when($filters['strength'], fn ($q, $strength) => $q->whereHas('medicine', fn ($m) => $m
                ->whereRaw("replace(lower(strength), ' ', '') like ?", ['%'.addcslashes(str_replace(' ', '', mb_strtolower($strength)), '%_\\').'%'])))
            ->when($filters['available'], fn ($q) => $q->where('status', ListingStatus::Available->value));

        return match ($filters['sort']) {
            'expiry' => $query->orderByDesc('expiry_date'),
            'name' => $query->orderBy('brand_name'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /**
     * Read filters from the URL and ignore anything unexpected. Search must never fail because of a strange query string.
     */
    private function cleanFilters(Request $request): array
    {
        $text = fn (string $key, int $max = 80): string => is_string($request->query($key)) ? mb_substr(trim($request->query($key)), 0, $max) : '';
        $id = fn (string $key): ?int => ctype_digit($text($key)) ? (int) $text($key) : null;
        $oneOf = fn (string $key, array $allowed): string => in_array($text($key), $allowed, true) ? $text($key) : '';

        $divisionId = $id('division_id');
        $divisionId = $divisionId && Division::whereKey($divisionId)->exists() ? $divisionId : null;

        // A district only counts when it belongs to the chosen division (or no division is chosen).
        $districtId = $id('district_id');
        if ($districtId && ! \App\Models\District::whereKey($districtId)->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))->exists()) {
            $districtId = null;
        }

        return [
            'q' => $text('q'),
            'division_id' => $divisionId,
            'district_id' => $districtId,
            'type' => $oneOf('type', array_keys(config('medimatch.listing_types'))),
            'form' => $oneOf('form', config('medimatch.dosage_forms')),
            'strength' => $text('strength', 30),
            'available' => $request->boolean('available'),
            'sort' => $oneOf('sort', array_keys(config('medimatch.sort_options'))) ?: 'newest',
        ];
    }
}
