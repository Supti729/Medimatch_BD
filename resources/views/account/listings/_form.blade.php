{{--
    Create / edit listing form.
    $listing:   MedicineListing being edited, or null when creating
    $catalogue: listable catalogue medicines with their brand names
    $location:  ['division_id' => …, 'district_id' => …, 'upazila_id' => …] to pre-fill the area
--}}
@php
    $editing = $listing !== null;
    $rules = config('medimatch.listing_rules');
    $minExpiry = now()->addMonths($rules['min_shelf_life_months'])->format('Y-m');
    $wasApproved = $editing && in_array($listing->status->value, ['available', 'paused', 'unavailable'], true);
    $currentMedicine = (string) old('medicine_id', $listing?->medicine_id);
    $imageCount = $editing ? $listing->images->count() : 0;
@endphp

<form method="POST" action="{{ $editing ? route('listings.update', $listing) : route('listings.store') }}" enctype="multipart/form-data" class="space-y-6" data-loading>
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <x-alert tone="danger" title="Please check the form">
            Some details need your attention. They are also marked below.
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->unique() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <x-card title="Medicine" description="Choose the medicine from the catalogue, then copy the brand name and other details from the pack.">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.select name="medicine_id" label="Medicine (generic name, strength and form)" required placeholder="Select from the catalogue"
                    data-brand-list="brand-suggestions"
                    hint="Not in the list? It may not be in the catalogue yet, or it may be a medicine that cannot be listed, such as controlled or refrigerated medicines.">
                    @foreach ($catalogue->groupBy('generic_name') as $generic => $medicines)
                        <optgroup label="{{ $generic }}">
                            @foreach ($medicines as $medicine)
                                <option value="{{ $medicine->id }}" data-brands="{{ $medicine->aliases->pluck('brand_name')->implode('|') }}" @selected($currentMedicine === (string) $medicine->id)>
                                    {{ $medicine->generic_name }} {{ $medicine->strength }} · {{ $medicine->dosage_form }}{{ $medicine->requires_prescription ? ' (prescription medicine)' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-form.select>
            </div>

            <x-form.input name="brand_name" label="Brand name on the pack" :value="$listing?->brand_name" required maxlength="100"
                list="brand-suggestions" autocomplete="off" placeholder="e.g. Napa" />
            <datalist id="brand-suggestions"></datalist>

            <x-form.input name="batch_number" label="Batch number" :value="$listing?->batch_number" optional maxlength="50" hint="Printed near the expiry date." />

            <x-form.input name="quantity" label="Quantity" type="number" :value="$listing?->quantity" required min="1" :max="$rules['max_quantity']" inputmode="numeric" />
            <x-form.select name="unit" label="Unit" required placeholder="Select unit" :selected="$listing?->unit"
                :options="array_combine(config('medimatch.units'), array_map('ucfirst', config('medimatch.units')))" />

            <x-form.input name="expiry" label="Expiry month" type="month" :value="$listing?->expiry_date?->format('Y-m')" required :min="$minExpiry"
                :hint="'At least '.$rules['min_shelf_life_months'].' months must be left before it expires.'" />
            <x-form.select name="packaging" label="Packaging condition" required placeholder="Select condition" :selected="$listing?->packaging"
                :options="config('medimatch.packaging')" hint="Opened or loose medicines cannot be listed." />
        </div>
    </x-card>

    <x-card title="Listing type">
        <div class="space-y-3">
            @php $currentType = old('type', $listing?->type ?? 'donation'); @endphp
            <label class="flex cursor-pointer items-start gap-3 rounded-control border border-line p-4 has-checked:border-brand-300 has-checked:bg-brand-50">
                <input type="radio" name="type" value="donation" @checked($currentType === 'donation') class="mt-0.5 size-4 accent-brand-600">
                <span class="text-sm"><span class="block font-semibold">Donation</span><span class="text-ink-soft">Free of charge. You must not ask for money.</span></span>
            </label>
            @if (config('medimatch.allow_affordable_access'))
                <label class="flex cursor-pointer items-start gap-3 rounded-control border border-line p-4 has-checked:border-brand-300 has-checked:bg-brand-50">
                    <input type="radio" name="type" value="affordable_access" @checked($currentType === 'affordable_access') class="mt-0.5 size-4 accent-brand-600">
                    <span class="text-sm"><span class="block font-semibold">Affordable access</span><span class="text-ink-soft">Offered at a low price, agreed with the requester. Only where the law allows it: selling medicines may need a drug licence in Bangladesh.</span></span>
                </label>
            @else
                <div class="flex items-start gap-3 rounded-control border border-line bg-canvas p-4 opacity-70">
                    <input type="radio" disabled class="mt-0.5 size-4" aria-label="Affordable access (switched off)">
                    <span class="text-sm"><span class="block font-semibold">Affordable access</span><span class="text-ink-soft">Switched off by the administrators at the moment.</span></span>
                </div>
            @endif
        </div>
        <x-form.field name="type" id="type" />
    </x-card>

    <x-card title="Photos" :description="'Up to '.$rules['max_photos'].' clear photos of the pack, including the expiry date. They help moderators approve your listing.'">
        @if ($editing && $imageCount)
            <fieldset>
                <legend class="text-sm font-medium">Current photos</legend>
                <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($listing->images as $image)
                        <li class="overflow-hidden rounded-control border border-line">
                            <img src="{{ $image->url() }}" alt="Photo {{ $loop->iteration }} of {{ $listing->brand_name }}" class="aspect-square w-full object-cover">
                            <label class="flex items-center gap-2 px-3 py-2 text-xs text-ink-soft">
                                <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="size-4 accent-danger-600" @checked(in_array((string) $image->id, (array) old('remove_images', []), true))>
                                Remove
                            </label>
                        </li>
                    @endforeach
                </ul>
            </fieldset>
        @endif

        <div @class(['mt-5' => $editing && $imageCount])>
            <label for="images" class="block text-sm font-medium">{{ $editing && $imageCount ? 'Add photos' : 'Photos' }} <span class="font-normal text-ink-muted">(optional)</span></label>
            <input type="file" name="images[]" id="images" multiple accept="image/jpeg,image/png,image/webp" aria-describedby="images-hint"
                @error('images') aria-invalid="true" @enderror
                class="mt-1.5 block w-full rounded-control border border-line bg-surface text-sm text-ink-soft file:mr-4 file:min-h-11 file:cursor-pointer file:border-0 file:bg-brand-50 file:px-4 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100">
            @if ($errors->has('images') || $errors->has('images.*'))
                <p class="mt-1.5 text-sm text-danger-700">{{ $errors->first('images') ?: $errors->first('images.*') }}</p>
            @else
                <p id="images-hint" class="mt-1.5 text-sm text-ink-muted">JPG, PNG or WebP, up to 2 MB each. Do not photograph prescriptions or anything with a patient's name.</p>
            @endif
        </div>
    </x-card>

    <x-card title="Pick-up area" description="Only the upazila and district are shown publicly. The exact address is shown only to the person whose request you accept.">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-form.location by="id" required with-upazila :division="$location['division_id'] ?? null" :district="$location['district_id'] ?? null" :upazila="$location['upazila_id'] ?? null" />
        </div>
        <div class="mt-5">
            <x-form.textarea name="pickup_address" label="Exact pick-up address" rows="2" required maxlength="300"
                :value="$listing?->pickup_address"
                hint="House, road, area and a landmark. Kept private and stored encrypted." />
        </div>
    </x-card>

    <x-card title="Safety confirmation" description="Please confirm each point every time you save.">
        <div class="space-y-4">
            @foreach ([
                'confirm_sealed' => 'The medicine is unopened and in its original packaging.',
                'confirm_expiry' => 'The expiry date I entered matches the pack, and it has not expired.',
                'confirm_storage' => 'It has been stored in a cool, dry place as the pack instructs.',
                'confirm_allowed' => 'It is not a controlled medicine, an injection, or a medicine that needs refrigeration.',
            ] as $name => $label)
                <x-form.checkbox :name="$name" :label="$label" required />
            @endforeach
        </div>
        <x-slot:footer>
            <a href="{{ route('safety') }}" target="_blank" class="text-sm font-semibold text-brand-700 hover:underline">Read the safety guidelines</a>
        </x-slot:footer>
    </x-card>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <x-button :href="route('listings.index')" variant="ghost">Cancel</x-button>
        @if ($wasApproved)
            <x-button type="submit" name="action" value="submit">Save and send for review</x-button>
        @else
            <x-button type="submit" name="action" value="draft" variant="secondary">Save as draft</x-button>
            <x-button type="submit" name="action" value="submit">Submit for review</x-button>
        @endif
    </div>
    @if ($wasApproved)
        <p class="text-right text-sm text-ink-muted">This listing is already approved. Saving changes hides it until a moderator reviews it again.</p>
    @endif
</form>
