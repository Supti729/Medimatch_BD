<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\Reservation;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            // Counted live from the database.
            'stats' => [
                'listings' => MedicineListing::publiclyVisible()->count(),
                'requests' => MedicineRequest::count(),
                'completed' => Reservation::where('status', ReservationStatus::Completed->value)->count(),
                'districts' => MedicineListing::publiclyVisible()->distinct()->count('district_id'),
            ],
            'latestListings' => MedicineListing::publiclyVisible()
                ->where('status', 'available')
                ->with(MedicineListing::CARD_RELATIONS)
                ->latest('published_at')
                ->limit(3)
                ->get()
                ->map->toCard(),
        ]);
    }
}
