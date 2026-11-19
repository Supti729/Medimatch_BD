<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\MedicineListing;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Asking for a listing, and the donor's and requester's answers.
 * The rules live in ReservationService; this controller only connects them to forms.
 */
class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function store(Request $request, MedicineListing $listing): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:300'],
        ], [], ['message' => 'note to the donor']);

        $this->reservations->request($listing, $request->user(), $data['message'] ?? null);

        return back()->with('success', 'Your request was sent. The donor will accept or decline it.');
    }

    public function accept(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->accept($reservation, $request->user());

        return back()->with('success', 'You reserved this medicine for '.$reservation->requester->name.'. Other people who asked were told it is no longer available.');
    }

    public function decline(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->decline($reservation, $request->user());

        return back()->with('success', 'The request was declined.');
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->cancel($reservation, $request->user());

        return back()->with('success', 'The reservation was cancelled.');
    }

    public function complete(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->complete($reservation, $request->user());

        return back()->with('success', 'The handover was marked as completed. Thank you.');
    }
}
