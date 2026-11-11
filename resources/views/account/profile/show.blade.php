@php $initial = mb_strtoupper(mb_substr($user['name'], 0, 1)); @endphp

<x-layouts.app title="Profile" :preview="$usingSampleData">
    <x-page-header title="Profile" description="Your account details, and what other people can see about you.">
        <x-button :href="route('profile.edit')" icon="pencil-square">Edit profile</x-button>
    </x-page-header>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Private view --}}
        <x-card title="Your details" description="Only you can see this.">
            <x-slot:actions><x-badge tone="neutral">Private</x-badge></x-slot:actions>

            <div class="flex items-center gap-4">
                <span class="flex size-16 items-center justify-center rounded-full bg-brand-600 text-2xl font-semibold text-white" aria-hidden="true">{{ $initial }}</span>
                <div>
                    <p class="text-lg font-semibold">{{ $user['name'] }}</p>
                    <p class="text-sm text-ink-muted">Member since {{ $user['joined']->format('F Y') }}</p>
                </div>
            </div>

            <x-detail-list class="mt-6 border-t border-line pt-6" :items="[
                'Email' => $user['email'],
                'Phone number' => $user['phone'],
                'Division' => $user['division'],
                'District' => $user['district'],
                'Upazila / Thana' => $user['upazila'],
                'Account verification' => $user['verified'] ? 'Verified' : 'Not verified',
            ]" />

            <x-slot:footer>
                <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
                    <a href="{{ route('settings.privacy') }}" class="text-brand-700 hover:underline">Privacy settings</a>
                    <a href="{{ route('settings.security') }}" class="text-brand-700 hover:underline">Security settings</a>
                    <a href="{{ route('verification.index') }}" class="text-brand-700 hover:underline">Verification Center</a>
                </div>
            </x-slot:footer>
        </x-card>

        {{-- Public view --}}
        <x-card title="Public profile" description="This is all that other users see.">
            <x-slot:actions><x-badge tone="info">Public</x-badge></x-slot:actions>

            <div class="rounded-card border border-dashed border-line bg-canvas p-5">
                <div class="flex items-center gap-4">
                    <span class="flex size-14 items-center justify-center rounded-full bg-brand-600 text-xl font-semibold text-white" aria-hidden="true">
                        @if ($user['privacy']['show_avatar']) {{ $initial }} @else <x-icon name="user" class="size-6" /> @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-base font-semibold">{{ $user['name'] }}</p>
                        <p class="text-sm text-ink-muted">Member since {{ $user['joined']->format('M Y') }}</p>
                    </div>
                    @if ($user['verified'] && $user['privacy']['show_verified_badge'])
                        <x-badge tone="success">Verified</x-badge>
                    @endif
                </div>
                @if ($user['privacy']['show_contributions'])
                    <p class="mt-4 border-t border-line pt-4 text-sm text-ink-soft">
                        <span class="font-semibold text-ink">{{ $stats['completed'] }}</span> completed {{ Str::plural('handover', $stats['completed']) }}
                    </p>
                @endif
            </div>

            <h3 class="mt-6 text-sm font-semibold">Never shown to others</h3>
            <ul class="mt-2 grid gap-2 text-sm text-ink-soft sm:grid-cols-2">
                @foreach (['Phone number', 'Email address', 'Exact address', 'Prescriptions and documents', 'Who you have exchanged with', 'Your requests'] as $item)
                    <li class="flex items-center gap-2"><x-icon name="eye-slash" class="size-4 text-ink-muted" /> {{ $item }}</li>
                @endforeach
            </ul>
        </x-card>
    </div>
</x-layouts.app>
