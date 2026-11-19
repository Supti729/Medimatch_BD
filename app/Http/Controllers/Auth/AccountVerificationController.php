<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AccountVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isVerified()) {
            return redirect()->route('dashboard');
        }

        $channel = $user->primaryChannel();

        return view('auth.verify', [
            'channel' => $channel,
            // Show where the code went without printing the full address
            'destination' => $channel === 'email'
                ? Str::mask($user->email, '*', 2, max(1, strpos($user->email, '@') - 2))
                : Str::mask($user->phone, '*', 3, 5),
            'verification' => session('verification'),
        ]);
    }

    public function verify(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();
        if ($user->isVerified()) {
            return redirect()->route('dashboard');
        }

        $result = $codes->verify($user, $request->string('code'));

        if ($result !== 'verified') {
            throw ValidationException::withMessages(['code' => match ($result) {
                'expired' => 'This code has expired. Send a new code and try again.',
                'too_many_attempts' => 'Too many wrong codes. Send a new code and try again.',
                default => 'That code is not correct.',
            }]);
        }

        return redirect()->route('dashboard')->with('success', 'Your account is verified. Welcome to Medimatch BD.');
    }

    public function resend(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        if ($request->user()->isVerified()) {
            return redirect()->route('dashboard');
        }

        $result = $codes->send($request->user());

        return back()->with('verification', $result)->with('success', 'A new code was created.');
    }
}
