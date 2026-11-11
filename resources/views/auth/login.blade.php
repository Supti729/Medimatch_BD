@php $ready = Route::has('login.store'); @endphp

<x-layouts.auth title="Log in" heading="Welcome back" subheading="Log in with your email address or phone number.">
    <x-not-connected route="login.store" feature="Logging in" class="mb-5" />

    <form class="space-y-5" @if ($ready) method="POST" action="{{ route('login.store') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
        @csrf

        <x-form.input name="login" label="Email or phone number" required autofocus maxlength="150"
            autocomplete="username" placeholder="you@example.com or 01XXXXXXXXX" />

        <div>
            <x-form.input name="password" label="Password" type="password" required autocomplete="current-password" />
            <p class="mt-2 text-right">
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:underline">Forgot your password?</a>
            </p>
        </div>

        <x-form.checkbox name="remember" label="Keep me logged in on this device" />

        <x-button type="submit" block :disabled="! $ready">Log in</x-button>
    </form>

    <x-slot:footer>
        New to Medimatch BD?
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Create an account</a>
    </x-slot:footer>
</x-layouts.auth>
