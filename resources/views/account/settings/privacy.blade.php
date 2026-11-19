<x-layouts.app title="Privacy settings">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Privacy settings" description="Choose what appears on your public profile. Your contact details are always private." />

        <form method="POST" action="{{ route('settings.privacy.update') }}" class="mt-6 space-y-6" data-loading>
            @csrf
            @method('PUT')

            <x-card title="Public profile">
                <div class="space-y-5">
                    <x-form.checkbox name="show_avatar" label="Show my profile picture" hint="If off, others see the first letter of your name." :checked="$user->show_avatar" />
                    <x-form.checkbox name="show_contributions" label="Show how many donations I have completed" hint="Only the number is shown, never the details." :checked="$user->show_contributions" />
                    <x-form.checkbox name="show_verified_badge" label="Show my verified badge" :checked="$user->show_verified_badge" />
                </div>
                <x-slot:footer>
                    <a href="{{ route('people.show', $user) }}" class="text-sm font-semibold text-brand-700 hover:underline">See my public profile</a>
                </x-slot:footer>
            </x-card>

            <x-card title="Messages">
                <x-form.checkbox name="matched_messages_only" label="Only people I am matched with can message me"
                    hint="Recommended. Messaging is added in a later part of the project; this choice is saved now and applied then."
                    :checked="$user->matched_messages_only" />
            </x-card>

            <x-card title="Always private" description="These cannot be made public.">
                <ul class="grid gap-2 text-sm text-ink-soft sm:grid-cols-2">
                    @foreach (['Phone number', 'Email address', 'Exact address', 'Prescriptions and documents', 'Messages', 'Your requests'] as $item)
                        <li class="flex items-center gap-2"><x-icon name="lock-closed" class="size-4 text-brand-600" /> {{ $item }}</li>
                    @endforeach
                </ul>
            </x-card>

            <div class="flex justify-end">
                <x-button type="submit">Save settings</x-button>
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
