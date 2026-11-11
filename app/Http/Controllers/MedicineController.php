<?php

namespace App\Http\Controllers;

use App\Support\SampleListings;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Public browsing of medicine listings.
 *
 * Front-end part: reads from SampleListings.
 * Backend part (B2/B3): the same filters will run as database queries.
 */
class MedicineController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): View
    {
        $filters = $this->cleanFilters($request);

        $results = SampleListings::search($filters);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $listings = new LengthAwarePaginator(
            $results->forPage($page, self::PER_PAGE)->values(),
            $results->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('medicines.index', [
            'listings' => $listings,
            'filters' => $filters,
            'view' => $request->query('view') === 'list' ? 'list' : 'grid',
            'activeFilterCount' => count(array_filter(
                array_diff_key($filters, ['q' => 1, 'sort' => 1])
            )),
            'usingSampleData' => true,
        ]);
    }

    public function show(int $listing): View
    {
        $found = SampleListings::find($listing);

        // Unknown, blocked and unreviewed listings all look the same to visitors: not found.
        abort_if($found === null, 404);

        return view('medicines.show', [
            'listing' => $found,
            'similar' => SampleListings::similarTo($found),
            'usingSampleData' => true,
        ]);
    }

    /**
     * Read the search filters from the URL and ignore anything unexpected.
     * Search must never fail because of a strange query string.
     */
    private function cleanFilters(Request $request): array
    {
        $text = fn (string $key, int $max = 80): string => is_string($request->query($key))
            ? mb_substr(trim($request->query($key)), 0, $max)
            : '';

        $oneOf = fn (string $key, array $allowed): string => in_array($text($key), $allowed, true) ? $text($key) : '';

        $divisions = config('bangladesh.divisions');
        $division = $oneOf('division', array_keys($divisions));

        // A district only counts when it belongs to the chosen division (or no division is chosen).
        $districts = $division ? $divisions[$division] : array_merge(...array_values($divisions));

        return [
            'q' => $text('q'),
            'division' => $division,
            'district' => $oneOf('district', $districts),
            'type' => $oneOf('type', array_keys(config('medimatch.listing_types'))),
            'form' => $oneOf('form', config('medimatch.dosage_forms')),
            'strength' => $text('strength', 30),
            'available' => $request->boolean('available'),
            'sort' => $oneOf('sort', array_keys(config('medimatch.sort_options'))) ?: 'newest',
        ];
    }
}
