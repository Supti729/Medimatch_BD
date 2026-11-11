<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's own medicine listings.
 * Front-end part: sample data only. Saving is added in backend push B2.
 */
class ListingController extends Controller
{
    /** Tabs on the My Listings page: tab key => statuses it contains. */
    public const TABS = [
        'all' => null,
        'active' => ['available', 'requested', 'reserved', 'paused'],
        'review' => ['draft', 'pending_review', 'rejected'],
        'closed' => ['completed', 'cancelled', 'unavailable', 'blocked'],
    ];

    public function index(Request $request): View
    {
        $all = SampleAccount::listings();
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'all';

        return view('account.listings.index', [
            'listings' => self::TABS[$tab] ? $all->whereIn('status', self::TABS[$tab])->values() : $all,
            'tab' => $tab,
            'counts' => collect(self::TABS)->map(fn (?array $statuses) => $statuses ? $all->whereIn('status', $statuses)->count() : $all->count()),
            'usingSampleData' => true,
        ]);
    }

    public function create(): View
    {
        return view('account.listings.create', [
            'user' => SampleAccount::user(),
            'usingSampleData' => true,
        ]);
    }

    public function edit(int $listing): View
    {
        $found = SampleAccount::findListing($listing);

        // Backend part: this becomes a policy check so users can only edit their own listings.
        abort_if($found === null, 404);

        return view('account.listings.edit', [
            'listing' => $found,
            'usingSampleData' => true,
        ]);
    }
}
