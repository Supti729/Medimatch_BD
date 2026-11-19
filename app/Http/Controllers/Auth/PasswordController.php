<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Change password from Security settings (user is signed in).
 */
class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.different' => 'Choose a password that is different from your current one.',
        ]);

        $user = $request->user();
        $user->password = $validated['password'];
        $user->save();

        // Sign out this account's other browser sessions
        Auth::logoutOtherDevices($validated['password']);

        return back()->with('success', 'Your password was updated.');
    }
}
