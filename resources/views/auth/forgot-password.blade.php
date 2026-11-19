<x-layouts.auth title="Forgot password" heading="Reset your password"
    subheading="Enter the email address or phone number on your account. If it matches an account, we will send instructions to reset your password.">
    @if ($devLink)
        <x-alert tone="warning" title="Development mode" class="mb-5">
            No email or SMS provider is configured, so nothing was really delivered. In local development the reset link is shown here instead:
            <a href="{{ $devLink }}" class="mt-2 block font-semibold break-all text-warning-700 underline">Open the reset link</a>
        </x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" data-loading>
        @csrf

        <x-form.input name="login" label="Email or phone number" required autofocus maxlength="150"
            autocomplete="username" placeholder="you@example.com or 01XXXXXXXXX" />

        <x-button type="submit" block>Send reset instructions</x-button>
    </form>

    <p class="mt-5 text-sm text-ink-muted">
        Reset emails are written to the application log in development because no mail provider is configured.
        SMS is not available, so accounts with only a phone number cannot reset their password outside development.
    </p>

    <x-slot:footer>
        Remembered it?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Back to log in</a>
    </x-slot:footer>
</x-layouts.auth>
