@php $ready = Route::has('verification.verify'); @endphp

<x-layouts.auth title="Verify your account" heading="Verify your account"
    subheading="Enter the 6-digit code sent to your email address or phone number.">
    <x-not-connected route="verification.verify" feature="Account verification" class="mb-5" />

    <x-alert tone="info" title="Development mode" class="mb-5">
        No email or SMS provider is configured for the prototype, so codes are not really delivered.
        When verification is connected, the code will be shown on this screen and clearly labelled as a development code.
    </x-alert>

    <form class="space-y-5" @if ($ready) method="POST" action="{{ route('verification.verify') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
        @csrf

        <x-form.input name="code" label="Verification code" required autofocus inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
            autocomplete="one-time-code" placeholder="000000" class="text-center text-lg tracking-[0.5em]" />

        <x-button type="submit" block :disabled="! $ready">Verify account</x-button>
    </form>

    <div class="mt-5 text-center">
        <x-button variant="ghost" size="sm" :disabled="! Route::has('verification.send')">Send a new code</x-button>
    </div>

    <x-slot:footer>
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Back to log in</a>
    </x-slot:footer>
</x-layouts.auth>
