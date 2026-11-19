<x-layouts.admin title="Medicines" preview>
    <x-page-header title="Medicine catalogue" description="The verified list of medicines that can be listed, their brand names, and the medicines that are restricted.">
        <x-button icon="plus" :disabled="! Route::has('admin.medicines.store')" title="{{ Route::has('admin.medicines.store') ? '' : 'Not connected yet' }}">Add medicine</x-button>
    </x-page-header>

    <x-alert tone="warning" title="Sample catalogue" class="mt-6">
        A real catalogue must be based on the DGDA registered medicine list and reviewed by a pharmacist. These entries only demonstrate the design.
    </x-alert>

    <x-card class="mt-6">
        <form method="GET" action="{{ route('admin.medicines.index') }}" role="search" class="flex gap-2">
            <div class="flex-1"><x-form.input name="q" aria-label="Search the catalogue" :value="$q" type="search" icon="magnifying-glass" placeholder="Generic or brand name" maxlength="80" /></div>
            <x-button type="submit">Search</x-button>
        </form>
    </x-card>

    <x-card class="mt-6" :padded="false">
        @if ($medicines->isEmpty())
            <x-empty-state icon="beaker" title="No medicine matches" description="Try the generic name." />
        @else
            <x-table :headers="['Generic name', 'Brand names (aliases)', 'Forms', 'Prescription', 'Eligibility']" caption="Medicine catalogue">
                @foreach ($medicines as $medicine)
                    <tr>
                        <th scope="row" class="px-5 py-3.5 font-medium">{{ $medicine['generic_name'] }}</th>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $medicine['brands'] ? implode(', ', $medicine['brands']) : '—' }}</td>
                        <td class="px-5 py-3.5 text-ink-soft">{{ implode(', ', $medicine['forms']) }}</td>
                        <td class="px-5 py-3.5">
                            @if ($medicine['requires_prescription']) <x-badge tone="warning">Required</x-badge> @else <x-badge>Not required</x-badge> @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if ($medicine['eligibility'] === 'allowed')
                                <x-badge tone="success" dot>Can be listed</x-badge>
                            @else
                                <x-badge tone="danger" dot>Cannot be listed</x-badge>
                                <span class="mt-1 block text-xs text-ink-muted">{{ $medicine['reason'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-layouts.admin>
