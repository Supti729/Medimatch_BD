<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

/**
 * The public view of a user: only name, picture, verified badge, join date and contribution count,
 * each shown only if the user allows it. Contact details and location are never shown here.
 */
class PublicProfileController extends Controller
{
    public function show(User $user): View
    {
        abort_if($user->isSuspended(), 404);

        return view('people.show', ['person' => $user]);
    }
}
