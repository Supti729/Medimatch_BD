<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's own medicine requests.
 * Front-end part: sample data only. Saving is added in backend push B3.
 */
class MedicineRequestController extends Controller
{
    public const TABS = [
        'all' => null,
        'open' => ['active', 'matching', 'match_found', 'verification_pending', 'reserved'],
        'closed' => ['completed', 'cancelled', 'expired'],
    ];

    /** The steps a request moves through, in order. */
    public const STEPS = ['active', 'matching', 'match_found', 'verification_pending', 'reserved', 'completed'];

    public function index(Request $request): View
    {
        $all = SampleAccount::requests();
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'all';

        return view('account.requests.index', [
            'requests' => self::TABS[$tab] ? $all->whereIn('status', self::TABS[$tab])->values() : $all,
            'tab' => $tab,
            'counts' => collect(self::TABS)->map(fn (?array $statuses) => $statuses ? $all->whereIn('status', $statuses)->count() : $all->count()),
            'usingSampleData' => true,
        ]);
    }

    public function create(Request $request): View
    {
        return view('account.requests.create', [
            'user' => SampleAccount::user(),
            // Lets "Request this medicine" on an empty search carry the search text over.
            'prefill' => is_string($request->query('q')) ? mb_substr($request->query('q'), 0, 80) : '',
            'usingSampleData' => true,
        ]);
    }

    public function show(int $medicineRequest): View
    {
        $found = SampleAccount::findRequest($medicineRequest);

        // Backend part: this becomes a policy check so users can only see their own requests.
        abort_if($found === null, 404);

        return view('account.requests.show', [
            'request' => $found,
            'matches' => in_array($found['status'], self::TABS['open'], true) ? SampleAccount::matchesFor($found) : collect(),
            'steps' => self::STEPS,
            'usingSampleData' => true,
        ]);
    }
}
