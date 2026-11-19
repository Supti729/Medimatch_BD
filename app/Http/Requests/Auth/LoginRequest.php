<?php

namespace App\Http\Requests\Auth;

use App\Support\AccountLookup;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Failed attempts allowed per minute for one login + IP address. */
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['login' => 'email or phone number'];
    }

    /**
     * Log the user in with an email address or a phone number.
     * The same message is used for every failure so it does not reveal which accounts exist.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credential = AccountLookup::credentialFor($this->string('login'));

        $ok = $credential !== null && Auth::attempt(
            [$credential['column'] => $credential['value'], 'password' => $this->string('password')->toString()],
            $this->boolean('remember'),
        );

        if (! $ok) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'login' => 'These details do not match an account. Check your email or phone number and password.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if (Auth::user()->isSuspended()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'login' => 'This account is suspended. Contact the Medimatch BD team if you think this is a mistake.',
            ]);
        }
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => "Too many attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
