<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleAccount;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $requests = SampleAccount::requests();

        // Recommend listings for the newest request that is still looking for a match.
        $openRequest = $requests->whereIn('status', ['active', 'matching', 'match_found'])->sortByDesc('created_at')
            ->first(fn (array $request) => SampleAccount::matchesFor($request)->isNotEmpty());

        return view('account.dashboard', [
            'user' => SampleAccount::user(),
            'stats' => SampleAccount::stats(),
            'activity' => SampleAccount::notifications()->take(5),
            'matchRequest' => $openRequest,
            'matches' => $openRequest ? SampleAccount::matchesFor($openRequest)->take(3) : collect(),
            'usingSampleData' => true,
        ]);
    }
}
