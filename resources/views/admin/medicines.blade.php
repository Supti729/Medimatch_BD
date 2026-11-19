@php
    $forms = array_unique(array_merge(config('medimatch.dosage_forms'), ['Injection']));
    $m = $editing;
@endphp

<x-layouts.admin title="Medicines">
    <x-page-header title="Medicine catalogue" description="The medicines that can be listed and requested, their brand names, and the medicines that are restricted." />

    <x-alert tone="warning" title="Demonstration catalogue" class="mt-6">
        A real catalogue must be based on the DGDA registered medicine list and reviewed by a pharmacist, including which medicines need a prescription and which must never be listed.
    </x-alert>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card>
                <form method="GET" action="{{ route('admin.medicines.index') }}" role="search" class="flex gap-2">
                    <div class="flex-1"><x-form.input name="q" aria-label="Search the catalogue" :value="$q" type="search" icon="magnifying-glass" placeholder="Generic or brand name" maxlength="80" /></div>
                    <x-button type="submit">Search</x-button>
                </form>
            </x-card>

            <x-card :padded="false">
                @if ($medicines->isEmpty())
                    <x-empty-state icon="beaker" title="No medicine matches" description="Try the generic name." />
                @else
                    <x-table :headers="['Medicine', 'Brand names', 'Prescription', 'Eligibility', 'Listings', '']" caption="Medicine catalogue">
                        @foreach ($medicines as $medicine)
                            <tr>
                                <th scope="row" class="px-5 py-3.5 font-medium">{{ $medicine->generic_name }} {{ $medicine->strength }}<span class="block text-xs font-normal text-ink-muted">{{ $medicine->dosage_form }}</span></th>
                                <td class="px-5 py-3.5 text-ink-soft">{{ $medicine->aliases->pluck('brand_name')->implode(', ') ?: '—' }}</td>
                                <td class="px-5 py-3.5">@if ($medicine->requires_prescription) <x-badge tone="warning">Required</x-badge> @else <x-badge>Not required</x-badge> @endif</td>
                                <td class="px-5 py-3.5">
                                    @if ($medicine->isListable())
                                        <x-badge tone="success" dot>Can be listed</x-badge>
                                    @else
                                        <x-badge tone="danger" dot>Cannot be listed</x-badge>
                                        <span class="mt-1 block text-xs text-ink-muted">{{ $medicine->restriction_reason }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 tabular-nums">{{ $medicine->listings_count }}</td>
                                <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.medicines.index', ['edit' => $medicine->id])" size="sm" variant="secondary">Edit</x-button></td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>
        </div>

        <aside>
            <x-card :title="$m ? 'Edit '.$m->generic_name.' '.$m->strength : 'Add a medicine'">
                <form method="POST" action="{{ $m ? route('admin.medicines.update', $m) : route('admin.medicines.store') }}" class="space-y-4" data-loading>
                    @csrf
                    @if ($m) @method('PUT') @endif
                    <x-form.input name="generic_name" label="Generic name" :value="$m?->generic_name" required maxlength="150" />
                    <div class="grid grid-cols-2 gap-4">
                        <x-form.input name="strength" label="Strength" :value="$m?->strength" required maxlength="50" placeholder="500 mg" />
                        <x-form.select name="dosage_form" label="Form" :selected="$m?->dosage_form" required placeholder="Select" :options="array_combine($forms, $forms)" />
                    </div>
                    <x-form.input name="brand_names" label="Brand names" :value="$m?->aliases->pluck('brand_name')->implode(', ')" optional maxlength="500" hint="Separate with commas, e.g. Napa, Ace" />
                    <x-form.checkbox name="requires_prescription" label="Needs a prescription check" :checked="(bool) $m?->requires_prescription" />
                    <x-form.select name="eligibility" label="Eligibility" :selected="$m?->eligibility ?? 'allowed'" required :options="['allowed' => 'Can be listed', 'not_allowed' => 'Cannot be listed']" />
                    <x-form.input name="restriction_reason" label="Reason it cannot be listed" :value="$m?->restriction_reason" maxlength="200" hint="Required when it cannot be listed, e.g. Controlled medicine." />
                    <div class="flex gap-2">
                        <x-button type="submit" class="flex-1">{{ $m ? 'Save changes' : 'Add medicine' }}</x-button>
                        @if ($m) <x-button :href="route('admin.medicines.index')" variant="ghost">Cancel</x-button> @endif
                    </div>
                </form>
            </x-card>
        </aside>
    </div>
</x-layouts.admin>
