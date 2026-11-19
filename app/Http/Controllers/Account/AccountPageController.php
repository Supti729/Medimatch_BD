<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Support\SampleAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Simpler signed-in pages that only display information in the front-end part.
 * Each one moves to its own controller when its backend feature is built.
 */
class AccountPageController extends Controller
{
    public function notifications(Request $request): View
    {
        $all = SampleAccount::notifications();
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        return view('account.notifications', [
            'notifications' => $filter === 'unread' ? $all->where('read', false)->values() : $all,
            'filter' => $filter,
            'unreadCount' => $all->where('read', false)->count(),
            'usingSampleData' => true,
        ]);
    }

    /**
     * Finished and closed interactions, as donor or requester.
     */
    public function history(Request $request): View
    {
        $user = $request->user();
        $roles = ['donated', 'received', 'requested'];
        $role = in_array($request->query('role'), $roles, true) ? $request->query('role') : '';

        $entries = Reservation::involving($user)
            ->whereIn('status', ['completed', 'cancelled', 'declined'])
            ->with(['listing.medicine', 'listing.owner', 'listing.upazila', 'listing.district', 'requester'])
            ->latest('updated_at')
            ->get()
            ->map(function (Reservation $r) use ($user) {
                $donor = $r->listing->user_id === $user->id;

                return [
                    'id' => $r->id,
                    // donated = I gave it; received = I got it; requested = I asked but it did not happen
                    'role' => $donor ? 'donated' : ($r->status->value === 'completed' ? 'received' : 'requested'),
                    'medicine' => $r->listing->brand_name.' '.$r->listing->medicine->strength.' · '.$r->listing->quantity.' '.$r->listing->unit,
                    'with' => $donor ? $r->requester->name : $r->listing->owner->name,
                    'status' => $r->status->value,
                    'date' => $r->completed_at ?? $r->updated_at,
                    'area' => $r->listing->upazila->name.', '.$r->listing->district->name,
                ];
            })
            ->when($role, fn ($c) => $c->where('role', $role)->values());

        return view('account.history', [
            'entries' => $entries,
            'role' => $role,
            'roles' => $roles,
        ]);
    }

    public function verification(): View
    {
        return view('account.verification', [
            'submissions' => SampleAccount::verifications(),
            'requests' => SampleAccount::requests()->where('requires_prescription', true)
                ->whereIn('status', ['active', 'matching', 'match_found', 'verification_pending'])->values(),
            'usingSampleData' => true,
        ]);
    }

}
