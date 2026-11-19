<x-layouts.app title="Dashboard">
    <x-page-header :title="'Welcome, '.$user->name" description="Here is what is happening with your listings and requests.">
        <x-button :href="route('medicines.index')" variant="secondary" icon="magnifying-glass">Find medicine</x-button>
    </x-page-header>

    {{-- Numbers --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5" aria-label="Your numbers">
        <x-stat-card label="Active listings" :value="$stats['active_listings']" icon="gift" tone="accent" :href="route('listings.index', ['tab' => 'active'])" />
        <x-stat-card label="Active requests" :value="$stats['active_requests']" icon="clipboard-document-list" tone="info" :href="route('requests.index', ['tab' => 'open'])" />
        <x-stat-card label="Pending verification" :value="$stats['pending_verification']" icon="shield-check" tone="warning" :href="route('requests.index', ['tab' => 'open'])" />
        <x-stat-card label="Reserved" :value="$stats['reserved']" icon="clock" tone="info" />
        <x-stat-card label="Completed" :value="$stats['completed']" icon="check-circle" tone="success" :href="route('history.index')" />
    </section>

    {{-- Quick actions --}}
    <section class="mt-6 grid gap-4 md:grid-cols-3" aria-label="Quick actions">
        @foreach ([
            [route('medicines.index'), 'magnifying-glass', 'Find medicine', 'Search listings near you.', 'bg-brand-50 text-brand-700'],
            [route('listings.create'), 'gift', 'Donate medicine', 'List a sealed medicine you no longer need.', 'bg-accent-50 text-accent-700'],
            [route('requests.create'), 'clipboard-document-list', 'Request medicine', 'Ask for a medicine and get matched.', 'bg-info-50 text-info-700'],
        ] as [$url, $icon, $title, $text, $colour])
            <a href="{{ $url }}" class="group flex items-center gap-4 rounded-card border border-line bg-surface p-5 shadow-card transition-shadow hover:shadow-raised">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-control {{ $colour }}"><x-icon :name="$icon" class="size-6" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-base font-semibold">{{ $title }}</span>
                    <span class="block text-sm text-ink-muted">{{ $text }}</span>
                </span>
                <x-icon name="arrow-right" class="size-5 text-ink-muted transition-transform group-hover:translate-x-0.5" />
            </a>
        @endforeach
    </section>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1.4fr_1fr]">
        {{-- Recommended matches --}}
        <x-card title="Recommended matches" :description="$matchRequest ? 'For your request: '.$matchRequest->medicine->generic_name.' '.$matchRequest->medicine->strength : null">
            @if ($matchRequest)
                <x-slot:actions>
                    <x-button :href="route('requests.show', $matchRequest)" size="sm" variant="secondary">View request</x-button>
                </x-slot:actions>
            @endif

            @if ($matches->isEmpty())
                <x-empty-state icon="sparkles" title="No matches yet" description="When a listing fits one of your open requests, it will appear here." class="py-6">
                    <x-button :href="route('requests.create')" size="sm">Request medicine</x-button>
                </x-empty-state>
            @else
                <div class="grid gap-4">
                    @foreach ($matches as $listing)
                        <x-medicine-card :listing="$listing" layout="list" class="shadow-none" />
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-ink-muted">
                    Matches are for the same medicine, strength and form. They are not medical advice. AI ranking is added in a later part of the project.
                </p>
            @endif
        </x-card>

        {{-- Handovers in progress --}}
        <x-card title="Handovers in progress" :padded="false">
            <x-slot:actions>
                <x-button :href="route('history.index')" size="sm" variant="secondary">History</x-button>
            </x-slot:actions>

            @if ($handovers->isEmpty())
                <x-empty-state icon="arrows-right-left" title="Nothing in progress" description="When you request a medicine, or someone requests yours, it appears here." />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($handovers as $handover)
                        @php $donor = $handover->listing->user_id === $user->id; @endphp
                        <li>
                            <a href="{{ $donor ? route('listings.edit', $handover->listing) : route('medicines.show', $handover->medicine_listing_id) }}" class="flex items-start gap-3 px-5 py-4 hover:bg-brand-50/60 sm:px-6">
                                <span @class(['flex size-9 shrink-0 items-center justify-center rounded-full', 'bg-accent-50 text-accent-700' => $donor, 'bg-info-50 text-info-700' => ! $donor]) aria-hidden="true">
                                    <x-icon :name="$donor ? 'gift' : 'hand-raised'" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold">{{ $handover->listing->brand_name }} {{ $handover->listing->medicine->strength }}</span>
                                    <span class="block text-sm text-ink-muted">
                                        {{ $donor ? $handover->requester->name.' asked for your listing' : 'You asked '.$handover->listing->owner->name }}
                                    </span>
                                </span>
                                <x-badge :tone="$handover->status->value === 'accepted' ? 'success' : 'warning'">{{ $donor && $handover->status->value === 'pending' ? 'Needs your answer' : $handover->status->label() }}</x-badge>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-layouts.app>
