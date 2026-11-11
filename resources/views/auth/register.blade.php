@php $ready = Route::has('register.store'); @endphp

<x-layouts.auth title="Create account" heading="Create your account" wide
    subheading="One free account lets you both request medicines and donate them.">
    <x-not-connected route="register.store" feature="Creating an account" class="mb-5" />

    <form class="space-y-8" enctype="multipart/form-data"
        @if ($ready) method="POST" action="{{ route('register.store') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
        @csrf

        <fieldset>
            <legend class="text-base font-semibold">About you</legend>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form.input name="name" label="Full name" required maxlength="100" autocomplete="name" />
                </div>
                <x-form.input name="email" label="Email" type="email" maxlength="150" autocomplete="email" placeholder="you@example.com" />
                <x-form.input name="phone" label="Phone number" type="tel" maxlength="14" autocomplete="tel" inputmode="tel"
                    placeholder="01XXXXXXXXX" hint="Bangladeshi mobile number." />
                <p class="text-sm text-ink-muted sm:col-span-2">
                    Give an email address, a phone number, or both. You can log in with either. They are never shown to other users.
                </p>
            </div>
        </fieldset>

        <fieldset>
            <legend class="text-base font-semibold">Password</legend>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <x-form.input name="password" label="Password" type="password" required minlength="8" autocomplete="new-password" hint="At least 8 characters." />
                <x-form.input name="password_confirmation" label="Confirm password" type="password" required minlength="8" autocomplete="new-password" />
            </div>
        </fieldset>

        <fieldset>
            <legend class="text-base font-semibold">Your area</legend>
            <p class="mt-1 text-sm text-ink-muted">Used to show medicines near you. Only the area is shown on your listings, never an address.</p>
            <div class="mt-4 grid gap-5 sm:grid-cols-3">
                <x-form.location required with-upazila />
            </div>
        </fieldset>

        <fieldset>
            <legend class="text-base font-semibold">Profile picture <span class="text-sm font-normal text-ink-muted">(optional)</span></legend>
            @php $errors ??= new \Illuminate\Support\ViewErrorBag; @endphp
            <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp" aria-describedby="avatar-hint" aria-label="Profile picture"
                class="mt-3 block w-full rounded-control border border-line bg-surface text-sm text-ink-soft file:mr-4 file:min-h-11 file:cursor-pointer file:border-0 file:bg-brand-50 file:px-4 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100">
            @error('avatar')
                <p class="mt-1.5 text-sm text-danger-700">{{ $message }}</p>
            @else
                <p id="avatar-hint" class="mt-1.5 text-sm text-ink-muted">JPG, PNG or WebP, up to 2 MB.</p>
            @enderror
        </fieldset>

        <x-form.checkbox name="terms" required>
            I agree to the <a href="{{ route('terms') }}" target="_blank" class="font-medium text-brand-700 underline">terms and conditions</a>
            and <a href="{{ route('privacy') }}" target="_blank" class="font-medium text-brand-700 underline">privacy policy</a>,
            and I understand this is an academic prototype.
        </x-form.checkbox>

        <x-button type="submit" size="lg" block :disabled="! $ready">Create account</x-button>
    </form>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Log in</a>
    </x-slot:footer>
</x-layouts.auth>
