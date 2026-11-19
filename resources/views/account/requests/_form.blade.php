{{--
    Create / edit request form.
    $medicineRequest: the request being edited, or null
    $catalogue:       listable catalogue medicines
    $preselect:       medicine id to pre-select (from a search, or the request being edited)
    $location:        ['division_id' => …, 'district_id' => …, 'upazila_id' => …]
--}}
@php
    $editing = $medicineRequest !== null;
    $current = (string) old('medicine_id', $preselect);
@endphp

<form method="POST" action="{{ $editing ? route('requests.update', $medicineRequest) : route('requests.store') }}" class="space-y-6" data-loading>
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <x-alert tone="danger" title="Please check the form">Some details need your attention. They are marked below.</x-alert>
    @endif

    <x-card title="Medicine" description="Choose the exact medicine. Matches must have the same generic name, strength and form.">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.select name="medicine_id" label="Medicine (generic name, strength and form)" required placeholder="Select from the catalogue"
                    hint="Brand names are listed in brackets. Any brand of the same medicine can match.">
                    @foreach ($catalogue->groupBy('generic_name') as $generic => $medicines)
                        <optgroup label="{{ $generic }}">
                            @foreach ($medicines as $medicine)
                                <option value="{{ $medicine->id }}" @selected($current === (string) $medicine->id)>
                                    {{ $medicine->generic_name }} {{ $medicine->strength }} · {{ $medicine->dosage_form }}
                                    @if ($medicine->aliases->isNotEmpty()) ({{ $medicine->aliases->pluck('brand_name')->implode(', ') }}) @endif
                                    {{ $medicine->requires_prescription ? '· prescription' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-form.select>
            </div>
            <x-form.input name="quantity" label="Quantity needed" type="number" :value="$medicineRequest?->quantity" required min="1" :max="config('medimatch.listing_rules.max_quantity')" inputmode="numeric" />
            <x-form.select name="unit" label="Unit" required placeholder="Select unit" :selected="$medicineRequest?->unit"
                :options="array_combine(config('medimatch.units'), array_map('ucfirst', config('medimatch.units')))" />
        </div>
    </x-card>

    <x-card title="Where you can collect" description="Matches in your district are shown first. Your address is never asked for.">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-form.location by="id" required with-upazila :division="$location['division_id'] ?? null" :district="$location['district_id'] ?? null" :upazila="$location['upazila_id'] ?? null" />
        </div>
    </x-card>

    <x-card title="Preferences">
        <fieldset>
            <legend class="text-sm font-medium">Type of assistance</legend>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                @foreach (['either' => ['Either', 'Show me all options.'], 'donation' => ['Donation only', 'Free medicines from donors.'], 'affordable_access' => ['Affordable access', 'From authorized partners.']] as $key => [$label, $text])
                    <label class="flex items-start gap-3 rounded-control border border-line p-4 has-checked:border-brand-300 has-checked:bg-brand-50">
                        <input type="radio" name="preference" value="{{ $key }}" @checked(old('preference', $medicineRequest?->preference ?? 'either') === $key) class="mt-0.5 size-4 accent-brand-600">
                        <span class="text-sm"><span class="block font-semibold">{{ $label }}</span><span class="text-ink-soft">{{ $text }}</span></span>
                    </label>
                @endforeach
            </div>
            @error('preference') <p class="mt-1.5 text-sm text-danger-700">{{ $message }}</p> @enderror
        </fieldset>

        <div class="mt-5">
            <x-form.textarea name="notes" label="Additional information" optional rows="3" maxlength="500" :value="$medicineRequest?->notes"
                placeholder="For example: can collect on weekends."
                hint="Do not write your diagnosis, medical history, phone number or address here. Donors you request from can read this text." />
        </div>
    </x-card>

    <x-form.checkbox name="confirm" required
        label="I am requesting this for genuine personal or family need, not to resell, and I understand a prescription check may be required." />

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <x-button :href="$editing ? route('requests.show', $medicineRequest) : route('requests.index')" variant="ghost">Cancel</x-button>
        <x-button type="submit">{{ $editing ? 'Save changes' : 'Post request' }}</x-button>
    </div>
</form>
