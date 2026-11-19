@php
    // Organization plans for the revenue-model demonstration. No payments are implemented.
    $plans = [
        ['name' => 'Community', 'price' => 'Free', 'for' => 'NGOs and charities', 'features' => ['Organization profile', 'Donation listings', 'Basic impact report']],
        ['name' => 'Institution', 'price' => 'Mock: BDT 5,000 / month', 'for' => 'Hospitals and clinics', 'features' => ['Everything in Community', 'Several staff accounts', 'Detailed impact reports']],
        ['name' => 'Pharmacy', 'price' => 'Mock: BDT 3,000 / month', 'for' => 'Licensed pharmacies', 'features' => ['Affordable-access listings', 'Stock availability alerts', 'Verification badge']],
    ];
@endphp

<x-layouts.admin title="Partners">
    <x-page-header title="Partner management" description="Accounts with the Partner role: NGOs, hospitals and licensed pharmacies." />

    <x-alert tone="info" title="How to add a partner" class="mt-6">
        Open the organization's user account and change its role to “Partner organization”. Check its licence and registration first.
        Affordable-access listings stay switched off in Settings until the legal basis is confirmed.
    </x-alert>

    <x-card class="mt-6" :padded="false">
        @if ($partners->isEmpty())
            <x-empty-state icon="building-office-2" title="No partner accounts yet" description="Partners are ordinary accounts given the Partner role by an administrator." />
        @else
            <x-table :headers="['Organization', 'District', 'Listings', 'Status', 'Joined', '']" caption="Partner organizations">
                @foreach ($partners as $partner)
                    <tr>
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">{{ $partner->name }}</th>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $partner->district?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 tabular-nums">{{ $partner->listings_count }}</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$partner->status" /></td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $partner->created_at->format('j M Y') }}</td>
                        <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.users.show', $partner)" size="sm" variant="secondary">View</x-button></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>

    <section class="mt-10" aria-labelledby="plans-heading">
        <div class="flex flex-wrap items-center gap-3">
            <h2 id="plans-heading" class="text-xl">Organization plans</h2>
            <x-badge tone="warning">Mock plans · no payments</x-badge>
        </div>
        <p class="mt-1 max-w-3xl text-sm text-ink-muted">
            A demonstration of the social-enterprise revenue model. Help for individuals stays free, personal and prescription data is never sold, and sponsors cannot bypass safety rules.
        </p>
        <div class="mt-5 grid gap-5 md:grid-cols-3">
            @foreach ($plans as $plan)
                <x-card>
                    <p class="text-sm font-semibold text-brand-700">{{ $plan['for'] }}</p>
                    <h3 class="mt-1 text-lg">{{ $plan['name'] }}</h3>
                    <p class="mt-2 text-sm font-semibold">{{ $plan['price'] }}</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink-soft">
                        @foreach ($plan['features'] as $feature)
                            <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 size-4 text-brand-600" /> {{ $feature }}</li>
                        @endforeach
                    </ul>
                </x-card>
            @endforeach
        </div>
    </section>
</x-layouts.admin>
