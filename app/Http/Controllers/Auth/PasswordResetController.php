<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\PasswordResetNotification;
use App\Support\AccountLookup;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Forgot password" for accounts that log in with an email address or a phone number.
 *
 * Uses Laravel's password reset token storage (hashed tokens that expire).
 * Email links are sent with a notification; SMS is not configured, so phone
 * users can only reset in local development, where the link is shown on screen.
 */
class PasswordResetController extends Controller
{
    private const GENERIC_STATUS = 'If an account exists with those details, reset instructions have been sent.';

    public function create(): View
    {
        return view('auth.forgot-password', ['devLink' => session('dev_reset_link')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['login' => ['required', 'string', 'max:150']]);

        $user = AccountLookup::find($request->string('login'));

        // The answer is the same whether or not the account exists, so the form cannot be used to find accounts.
        if (! $user || $user->isSuspended()) {
            return back()->with('status', self::GENERIC_STATUS);
        }

        $tokens = $this->tokens();

        if ($tokens->recentlyCreatedToken($user)) {
            return back()->with('status', self::GENERIC_STATUS);
        }

        $token = $tokens->create($user);
        $url = route('password.reset', ['token' => $token, 'login' => $request->string('login')->trim()->toString()]);

        if ($user->email) {
            $user->notify(new PasswordResetNotification($url));
        }

        $response = back()->with('status', self::GENERIC_STATUS);

        return config('medimatch.dev_codes_on_screen') ? $response->with('dev_reset_link', $url) : $response;
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'login' => is_string($request->query('login')) ? Str::limit($request->query('login'), 150, '') : '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = AccountLookup::find($request->string('login'));
        $tokens = $this->tokens();

        if (! $user || ! $tokens->exists($user, $request->string('token'))) {
            throw ValidationException::withMessages([
                'login' => 'This reset link is invalid or has expired. Please ask for a new one.',
            ]);
        }

        $user->password = $request->string('password')->toString();
        $user->setRememberToken(Str::random(60)); // logs out "remember me" sessions elsewhere
        $user->save();

        $tokens->delete($user);
        event(new PasswordReset($user));

        return redirect()->route('login')->with('success', 'Your password was changed. You can log in now.');
    }

    private function tokens(): TokenRepositoryInterface
    {
        return Password::broker()->getRepository();
    }
}
