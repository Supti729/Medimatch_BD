@php $ready = Route::has('password.email'); @endphp

<x-layouts.auth title="Forgot password" heading="Reset your password"
    subheading="Enter the email address or phone number on your account and we will send instructions to reset your password.">
    <x-not-connected route="password.email" feature="Password reset" class="mb-5" />

    <form class="space-y-5" @if ($ready) method="POST" action="{{ route('password.email') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
        @csrf

        <x-form.input name="login" label="Email or phone number" required autofocus maxlength="150"
            autocomplete="username" placeholder="you@example.com or 01XXXXXXXXX" />

        <x-button type="submit" block :disabled="! $ready">Send reset instructions</x-button>
    </form>

    <p class="mt-5 text-sm text-ink-muted">
        The prototype cannot send real emails or text messages yet, because no delivery provider is configured.
    </p>

    <x-slot:footer>
        Remembered it?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Back to log in</a>
    </x-slot:footer>
</x-layouts.auth>
