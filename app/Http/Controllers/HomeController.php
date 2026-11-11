<?php

namespace App\Http\Controllers;

use App\Support\SampleListings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            // Community impact numbers must come from the database.
            // They stay null (shown as "—") until the backend part connects them.
            'stats' => [
                'listings' => null,
                'requests' => null,
                'completed' => null,
                'districts' => null,
            ],

            // Sample data until the listings table exists (see SampleListings).
            'latestListings' => SampleListings::search(['available' => true])->take(3),
            'usingSampleData' => true,
        ]);
    }
}
