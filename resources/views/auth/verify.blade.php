<x-layouts.auth title="Verify your account" heading="Verify your account"
    :subheading="'We created a 6-digit code for the '.($channel === 'email' ? 'email address ' : 'phone number ').$destination.'. Enter it below.'">

    @if ($verification['dev_code'] ?? null)
        <x-alert tone="warning" title="Development code" class="mb-5">
            @if ($channel === 'phone')
                SMS is not configured, so no text message was sent.
            @else
                No mail provider is configured, so the email was written to the application log instead of being delivered.
            @endif
            For local development, your code is:
            <span class="mt-2 block text-2xl font-semibold tracking-[0.3em] text-ink tabular-nums">{{ $verification['dev_code'] }}</span>
        </x-alert>
    @elseif ($channel === 'phone')
        <x-alert tone="info" title="SMS is not available yet" class="mb-5">
            Phone verification codes cannot be delivered because no SMS provider is configured. Please contact the project team.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('verification.verify') }}" class="space-y-5" data-loading>
        @csrf

        <x-form.input name="code" label="Verification code" required autofocus inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
            autocomplete="one-time-code" placeholder="000000" class="text-center text-lg tracking-[0.5em]"
            :hint="'The code expires after '.config('medimatch.verification_code_minutes').' minutes.'" />

        <x-button type="submit" block>Verify account</x-button>
    </form>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-4 text-center" data-loading>
        @csrf
        <x-button type="submit" variant="ghost" size="sm" icon="arrow-path">Send a new code</x-button>
    </form>

    <x-slot:footer>
        Wrong account?
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="font-semibold text-brand-700 hover:underline">Log out</button>
        </form>
    </x-slot:footer>
</x-layouts.auth>
