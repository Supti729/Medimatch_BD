@php $ready = Route::has('login.store'); @endphp

<x-layouts.auth title="Admin log in" heading="Administrator log in" subheading="For platform administrators and authorized reviewers only.">
    <x-not-connected route="login.store" feature="Administrator log in" class="mb-5" />

    <form class="space-y-5" @if ($ready) method="POST" action="{{ route('login.store') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
        @csrf
        <x-form.input name="login" label="Email or phone number" required autofocus maxlength="150" autocomplete="username" />
        <x-form.input name="password" label="Password" type="password" required autocomplete="current-password" />
        <x-button type="submit" block :disabled="! $ready">Log in</x-button>
    </form>

    <p class="mt-5 flex items-start gap-2 text-sm text-ink-muted">
        <x-icon name="shield-check" class="mt-0.5 size-4 text-brand-600" />
        Administrator access is granted by an existing administrator. It cannot be requested or chosen during registration, and every admin action is recorded.
    </p>

    <x-slot:footer>
        Not an administrator?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Go to the user log in</a>
    </x-slot:footer>
</x-layouts.auth>
