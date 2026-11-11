@php
    $passwordReady = Route::has('settings.password.update');
    $contactReady = Route::has('settings.contact.update');
@endphp

<x-layouts.app title="Security settings" :preview="$usingSampleData">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Security settings" description="Keep your account safe." />

        <div class="mt-6 space-y-6">
            <x-card title="Change password">
                <x-not-connected route="settings.password.update" feature="Changing your password" class="mb-5" />
                <form class="space-y-5" @if ($passwordReady) method="POST" action="{{ route('settings.password.update') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
                    @csrf
                    @if ($passwordReady) @method('PUT') @endif
                    <x-form.input name="current_password" label="Current password" type="password" required autocomplete="current-password" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="password" label="New password" type="password" required minlength="8" autocomplete="new-password" hint="At least 8 characters." />
                        <x-form.input name="password_confirmation" label="Confirm new password" type="password" required minlength="8" autocomplete="new-password" />
                    </div>
                    <x-button type="submit" :disabled="! $passwordReady">Update password</x-button>
                </form>
            </x-card>

            <x-card title="Email and phone number" description="You can log in with either one. Both stay private.">
                <x-not-connected route="settings.contact.update" feature="Changing your email or phone number" class="mb-5" />
                <form class="space-y-5" @if ($contactReady) method="POST" action="{{ route('settings.contact.update') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
                    @csrf
                    @if ($contactReady) @method('PUT') @endif
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="email" label="Email" type="email" :value="$user['email']" maxlength="150" autocomplete="email" />
                        <x-form.input name="phone" label="Phone number" type="tel" :value="$user['phone']" maxlength="14" autocomplete="tel" inputmode="tel" hint="Bangladeshi mobile number." />
                    </div>
                    <x-form.input name="confirm_password" id="contact-confirm-password" label="Your password" type="password" required autocomplete="current-password" hint="Needed to confirm this change." />
                    <x-button type="submit" :disabled="! $contactReady">Save contact details</x-button>
                </form>
            </x-card>

            <x-card title="Signed-in devices">
                <x-empty-state icon="computer-desktop" title="No sessions to show" class="py-6"
                    description="The devices where you are logged in will be listed here once sign-in is connected, with an option to log out everywhere else." />
            </x-card>

            <x-card title="Stay safe">
                <ul class="space-y-2.5 text-sm text-ink-soft">
                    @foreach ([
                        'Use a password you do not use on any other website.',
                        'Medimatch BD will never ask for your password in a message.',
                        'Log out after using a shared computer or phone.',
                    ] as $tip)
                        <li class="flex items-start gap-2.5"><x-icon name="shield-check" class="mt-0.5 size-5 text-brand-600" /> {{ $tip }}</li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.app>
