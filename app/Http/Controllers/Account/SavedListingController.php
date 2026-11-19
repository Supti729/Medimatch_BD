<?php

namespace App\Http\Controllers\Account;

use App\Models\MedicineListing;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedListingController extends Controller
{
    public function index(Request $request): View
    {
        $saved = $request->user()->savedListings()->with(MedicineListing::CARD_RELATIONS)->latest('saved_listings.created_at')->get();

        // A saved listing that is no longer public (cancelled, completed, blocked) is not shown in detail.
        [$visible, $gone] = $saved->partition(fn (MedicineListing $listing) => in_array($listing->status, \App\Enums\ListingStatus::publicStatuses(), true));

        return view('account.saved', [
            'listings' => $visible->map->toCard()->values(),
            'goneIds' => $gone->pluck('id')->all(),
        ]);
    }

    public function store(Request $request, MedicineListing $listing): RedirectResponse
    {
        abort_unless(MedicineListing::publiclyVisible()->whereKey($listing->id)->exists(), 404);

        $request->user()->savedListings()->syncWithoutDetaching([$listing->id]);

        return back()->with('success', 'Saved. You can find it under Saved medicines.');
    }

    public function destroy(Request $request, MedicineListing $listing): RedirectResponse
    {
        $request->user()->savedListings()->detach($listing->id);

        return back()->with('success', 'Removed from your saved medicines.');
    }
}
