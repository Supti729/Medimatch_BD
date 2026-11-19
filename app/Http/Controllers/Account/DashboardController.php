<?php

namespace App\Http\Controllers\Account;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\MatchingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MatchingService $matching): View
    {
        $user = $request->user();

        $listingCounts = $user->listings()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $requestCounts = $user->medicineRequests()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // Recommended matches for the newest open request that has any
        $matchRequest = null;
        $matches = collect();
        foreach ($user->medicineRequests()->where('status', RequestStatus::Active->value)->with('medicine')->latest()->limit(5)->get() as $candidate) {
            $found = $matching->forRequest($candidate, 3);
            if ($found->isNotEmpty()) {
                [$matchRequest, $matches] = [$candidate, $found];
                break;
            }
        }

        return view('account.dashboard', [
            'user' => $user,
            'stats' => [
                'active_listings' => $listingCounts->only(['available', 'requested'])->sum(),
                'active_requests' => $requestCounts->only([RequestStatus::Active->value])->sum(),
                'pending_verification' => $requestCounts->only([RequestStatus::VerificationPending->value])->sum(),
                'reserved' => $listingCounts->only([ListingStatus::Reserved->value])->sum() + $requestCounts->only([RequestStatus::Reserved->value])->sum(),
                'completed' => Reservation::involving($user)->where('status', ReservationStatus::Completed->value)->count(),
            ],
            'matchRequest' => $matchRequest,
            'matches' => $matches->map->toCard(),
            // Handovers in progress, as donor or requester
            'handovers' => Reservation::involving($user)->active()
                ->with(['listing.medicine', 'listing.owner', 'requester'])
                ->latest('updated_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
