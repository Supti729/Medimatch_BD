<x-layouts.app title="Profile">
    <x-page-header title="Profile" description="Your account details, and what other people can see about you.">
        <x-button :href="route('people.show', $user)" variant="secondary" icon="eye">View public profile</x-button>
        <x-button :href="route('profile.edit')" icon="pencil-square">Edit profile</x-button>
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Your details" description="Only you can see this.">
            <x-slot:actions><x-badge>Private</x-badge></x-slot:actions>

            <div class="flex items-center gap-4">
                <x-avatar :user="$user" size="lg" />
                <div>
                    <p class="text-lg font-semibold">{{ $user->name }}</p>
                    <p class="text-sm text-ink-muted">Member since {{ $user->created_at->format('F Y') }}</p>
                </div>
            </div>

            <x-detail-list class="mt-6 border-t border-line pt-6" :items="[
                'Email' => $user->email ? $user->email.($user->email_verified_at ? ' (verified)' : ' (not verified)') : null,
                'Phone number' => $user->phone ? $user->phone.($user->phone_verified_at ? ' (verified)' : ' (not verified)') : null,
                'Division' => $user->division?->name,
                'District' => $user->district?->name,
                'Upazila / Thana' => $user->upazila?->name,
                'Account type' => $user->role->label(),
            ]" />

            <x-slot:footer>
                <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
                    <a href="{{ route('settings.privacy') }}" class="text-brand-700 hover:underline">Privacy settings</a>
                    <a href="{{ route('settings.security') }}" class="text-brand-700 hover:underline">Security settings</a>
                </div>
            </x-slot:footer>
        </x-card>

        <x-card title="Public profile" description="This is all that other users see.">
            <x-slot:actions><x-badge tone="info">Public</x-badge></x-slot:actions>
            @include('people._card', ['person' => $user])

            <h3 class="mt-6 text-sm font-semibold">Never shown to others</h3>
            <ul class="mt-2 grid gap-2 text-sm text-ink-soft sm:grid-cols-2">
                @foreach (['Phone number', 'Email address', 'Exact address', 'Prescriptions and documents', 'Who you have exchanged with', 'Your requests'] as $item)
                    <li class="flex items-center gap-2"><x-icon name="eye-slash" class="size-4 text-ink-muted" /> {{ $item }}</li>
                @endforeach
            </ul>
            <x-slot:footer>
                <a href="{{ route('settings.privacy') }}" class="text-sm font-semibold text-brand-700 hover:underline">Change what is shown</a>
            </x-slot:footer>
        </x-card>
    </div>
</x-layouts.app>
