@php $ready = Route::has('password.update'); @endphp

<x-layouts.auth title="Choose a new password" heading="Choose a new password" subheading="Pick a password you do not use anywhere else.">
    <x-not-connected route="password.update" feature="Saving a new password" class="mb-5" />

    <form class="space-y-5" @if ($ready) method="POST" action="{{ route('password.update') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-form.input name="login" label="Email or phone number" :value="request('login')" required maxlength="150" autocomplete="username" />
        <x-form.input name="password" label="New password" type="password" required minlength="8" autocomplete="new-password" hint="At least 8 characters." />
        <x-form.input name="password_confirmation" label="Confirm new password" type="password" required minlength="8" autocomplete="new-password" />

        <x-button type="submit" block :disabled="! $ready">Save new password</x-button>
    </form>

    <x-slot:footer>
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Back to log in</a>
    </x-slot:footer>
</x-layouts.auth>
