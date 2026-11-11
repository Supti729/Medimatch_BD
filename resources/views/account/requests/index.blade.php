<x-layouts.app title="My requests" :preview="$usingSampleData">
    <x-page-header title="My requests" description="Medicines you have asked for. We look for matching listings while a request is open.">
        <x-button :href="route('requests.create')" icon="plus">New request</x-button>
    </x-page-header>

    <x-tabs class="mt-6" route="requests.index" :current="$tab" :counts="$counts" :tabs="['all' => 'All', 'open' => 'Open', 'closed' => 'Closed']" />

    @if ($requests->isEmpty())
        <x-card class="mt-6" :padded="false">
            <x-empty-state icon="clipboard-document-list" :title="$tab === 'all' ? 'You have not requested any medicine yet' : 'Nothing in this tab'"
                description="Post a request and you will be notified when a matching medicine is listed near you.">
                <x-button :href="route('requests.create')" icon="plus">New request</x-button>
            </x-empty-state>
        </x-card>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($requests as $request)
                <li class="relative flex flex-wrap items-center gap-4 rounded-card border border-line bg-surface p-5 shadow-card transition-shadow hover:shadow-raised">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-info-50 text-info-700" aria-hidden="true"><x-icon name="clipboard-document-list" class="size-6" /></span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold">
                                <a href="{{ route('requests.show', $request['id']) }}" class="after:absolute after:inset-0 after:rounded-card">
                                    {{ $request['name'] }} <span class="font-medium text-ink-soft">{{ $request['strength'] }}</span>
                                </a>
                            </h2>
                            <x-status-badge :status="$request['status']" />
                            @if ($request['requires_prescription'])
                                <x-badge tone="warning">Prescription check</x-badge>
                            @endif
                        </div>
                        <p class="mt-0.5 text-sm text-ink-muted">
                            {{ $request['generic_name'] }} · {{ $request['dosage_form'] }} · {{ $request['quantity'] }} {{ $request['unit'] }} ·
                            {{ $request['area'] }}, {{ $request['district'] }}
                        </p>
                    </div>

                    <p class="text-sm text-ink-muted">Requested {{ $request['created_at']->diffForHumans() }}</p>
                    <x-icon name="chevron-right" class="hidden size-5 text-ink-muted sm:block" />
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
