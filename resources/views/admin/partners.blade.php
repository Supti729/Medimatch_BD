<x-layouts.admin title="Partners" preview>
    <x-page-header title="Partner management" description="NGOs, hospitals and licensed pharmacies that apply to work with the platform.">
        <x-button icon="plus" :disabled="! Route::has('admin.partners.store')" title="{{ Route::has('admin.partners.store') ? '' : 'Not connected yet' }}">Add partner</x-button>
    </x-page-header>

    <x-alert tone="warning" title="Prototype" class="mt-6">
        The organizations below are fictional. A real partner would need its licence and registration checked before it could offer affordable-access listings.
    </x-alert>

    <x-card class="mt-6" :padded="false">
        <x-table :headers="['Organization', 'Type', 'District', 'Plan', 'Applied', 'Status']" caption="Partner organizations">
            @foreach ($partners as $partner)
                <tr>
                    <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">{{ $partner['name'] }}</th>
                    <td class="px-5 py-3.5 text-ink-soft">{{ $partner['type'] }}</td>
                    <td class="px-5 py-3.5 text-ink-soft">{{ $partner['district'] }}</td>
                    <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $partner['plan'] }}</td>
                    <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $partner['joined']->format('j M Y') }}</td>
                    <td class="px-5 py-3.5"><x-status-badge :status="$partner['status']" /></td>
                </tr>
            @endforeach
        </x-table>
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
