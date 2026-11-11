@php $ready = Route::has('settings.privacy.update'); @endphp

<x-layouts.app title="Privacy settings" :preview="$usingSampleData">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Privacy settings" description="Choose what appears on your public profile. Your contact details are always private." />

        <form class="mt-6 space-y-6" @if ($ready) method="POST" action="{{ route('settings.privacy.update') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
            @csrf
            @if ($ready) @method('PUT') @endif

            <x-card title="Public profile">
                <div class="space-y-5">
                    <x-form.checkbox name="show_avatar" label="Show my profile picture" hint="If off, others see a plain icon." :checked="$user['privacy']['show_avatar']" />
                    <x-form.checkbox name="show_contributions" label="Show how many handovers I have completed" hint="Only the number is shown, never the details." :checked="$user['privacy']['show_contributions']" />
                    <x-form.checkbox name="show_verified_badge" label="Show my verified badge" :checked="$user['privacy']['show_verified_badge']" />
                </div>
                <x-slot:footer>
                    <a href="{{ route('profile.show') }}" class="text-sm font-semibold text-brand-700 hover:underline">Preview my public profile</a>
                </x-slot:footer>
            </x-card>

            <x-card title="Messages">
                <x-form.checkbox name="matched_messages_only" label="Only people I am matched with can message me"
                    hint="Recommended. A match means their listing or request is linked to yours." :checked="$user['privacy']['matched_messages_only']" />
            </x-card>

            <x-card title="Always private" description="These cannot be made public.">
                <ul class="grid gap-2 text-sm text-ink-soft sm:grid-cols-2">
                    @foreach (['Phone number', 'Email address', 'Exact address', 'Prescriptions and documents', 'Messages', 'Your requests'] as $item)
                        <li class="flex items-center gap-2"><x-icon name="lock-closed" class="size-4 text-brand-600" /> {{ $item }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-not-connected route="settings.privacy.update" feature="Saving privacy settings" />

            <div class="flex justify-end">
                <x-button type="submit" :disabled="! $ready">Save settings</x-button>
            </div>
        </form>

        <x-card title="Your data" class="mt-6">
            <div class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="max-w-md">
                        <p class="text-sm font-semibold">Download my data</p>
                        <p class="text-sm text-ink-muted">A copy of your profile, listings, requests and messages.</p>
                    </div>
                    <x-button variant="secondary" size="sm" disabled title="Planned feature">Request a copy</x-button>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5">
                    <div class="max-w-md">
                        <p class="text-sm font-semibold text-danger-700">Close my account</p>
                        <p class="text-sm text-ink-muted">Cancels your open listings and requests and removes your profile.</p>
                    </div>
                    <x-button variant="danger" size="sm" disabled title="Planned feature">Close account</x-button>
                </div>
            </div>
            <x-slot:footer><p class="text-xs text-ink-muted">Data download and account closure are planned features and are not available in the prototype yet.</p></x-slot:footer>
        </x-card>
    </div>
</x-layouts.app>
