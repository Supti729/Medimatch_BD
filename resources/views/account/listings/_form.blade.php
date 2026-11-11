{{--
    Shared by the create and edit listing pages.
    $listing: existing values (edit) or null (create)
    $location: ['division' => ..., 'district' => ..., 'upazila' => ...] used to pre-fill the area
    $storeRoute: route name that will save the form once the backend exists
--}}
@php
    $ready = Route::has($storeRoute);
    $value = fn (string $key, $default = null) => $listing[$key] ?? $default;
    $forms = config('medimatch.dosage_forms');
    $locked = $locked ?? false;
@endphp

<form class="space-y-6" enctype="multipart/form-data"
    @if ($ready) method="POST" action="{{ $listing ? route($storeRoute, $listing['id']) : route($storeRoute) }}" data-loading @else onsubmit="event.preventDefault()" @endif>
    @csrf
    @if ($listing && $ready)
        @method('PUT')
    @endif

    <fieldset @disabled($locked) class="space-y-6">
        <x-card title="Medicine" description="Copy these details exactly from the pack.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Medicine (brand) name" :value="$value('name')" required maxlength="100" placeholder="e.g. Napa" />
                <x-form.input name="generic_name" label="Generic name" :value="$value('generic_name')" required maxlength="150" placeholder="e.g. Paracetamol" hint="Printed in smaller text under the brand name." />
                <x-form.input name="strength" label="Strength" :value="$value('strength')" required maxlength="50" placeholder="e.g. 500 mg" />
                <x-form.select name="dosage_form" label="Dosage form" :selected="$value('dosage_form')" required placeholder="Select form" :options="array_combine($forms, $forms)" />
                <x-form.input name="quantity" label="Quantity" type="number" :value="$value('quantity')" required min="1" max="1000" inputmode="numeric" />
                <x-form.input name="unit" label="Unit" :value="$value('unit')" required maxlength="30" placeholder="tablets, capsules, bottle…" />
                <x-form.input name="expiry" label="Expiry date" type="month" :value="$value('expiry')" required :min="now()->format('Y-m')" hint="Month and year printed on the pack." />
                <x-form.input name="batch_number" label="Batch number" :value="$value('batch_number')" optional maxlength="50" />
                <div class="sm:col-span-2">
                    <x-form.select name="packaging" label="Packaging condition" :selected="$value('packaging')" required placeholder="Select condition"
                        :options="['sealed_box' => 'Sealed, in the original box', 'sealed_strip' => 'Sealed strip or bottle, without the outer box']"
                        hint="Opened or loose medicines cannot be listed." />
                </div>
            </div>
        </x-card>

        <x-card title="Listing type">
            <div class="space-y-3">
                <label class="flex items-start gap-3 rounded-control border border-brand-300 bg-brand-50 p-4">
                    <input type="radio" name="type" value="donation" checked class="mt-0.5 size-4 accent-brand-600">
                    <span class="text-sm"><span class="block font-semibold">Donation</span><span class="text-ink-soft">Free of charge. You must not ask for money.</span></span>
                </label>
                <label class="flex items-start gap-3 rounded-control border border-line bg-canvas p-4 opacity-70">
                    <input type="radio" name="type" value="affordable_access" disabled class="mt-0.5 size-4">
                    <span class="text-sm"><span class="block font-semibold">Affordable access <x-badge class="ml-1">Partners only</x-badge></span><span class="text-ink-soft">Only for authorized partner organizations, where the law allows it.</span></span>
                </label>
            </div>
        </x-card>

        <x-card title="Photos" description="Clear photos of the front of the pack and the expiry date help reviewers approve your listing.">
            @php $errors ??= new \Illuminate\Support\ViewErrorBag; @endphp
            <input type="file" name="images[]" id="images" multiple accept="image/jpeg,image/png,image/webp" aria-label="Medicine photos" aria-describedby="images-hint"
                class="block w-full rounded-control border border-line bg-surface text-sm text-ink-soft file:mr-4 file:min-h-11 file:cursor-pointer file:border-0 file:bg-brand-50 file:px-4 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100">
            @error('images')
                <p class="mt-1.5 text-sm text-danger-700">{{ $message }}</p>
            @else
                <p id="images-hint" class="mt-1.5 text-sm text-ink-muted">Up to 4 photos, JPG, PNG or WebP, 2 MB each. Do not photograph prescriptions or anything with a patient name.</p>
            @enderror
        </x-card>

        <x-card title="Pick-up area" description="Only the upazila and district are shown publicly, never an address.">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-form.location required with-upazila :division="$location['division'] ?? null" :district="$location['district'] ?? null" :upazila="$location['upazila'] ?? null" />
            </div>
        </x-card>

        <x-card title="Safety confirmation">
            <div class="space-y-4">
                @foreach ([
                    'confirm_sealed' => 'The medicine is unopened and in its original packaging.',
                    'confirm_expiry' => 'The expiry date I entered matches the pack, and it has not expired.',
                    'confirm_storage' => 'It has been stored in a cool, dry place as the pack instructs.',
                    'confirm_allowed' => 'It is not a controlled medicine, an injection, or a medicine that needs refrigeration.',
                ] as $name => $label)
                    <x-form.checkbox :name="$name" :label="$label" required :checked="(bool) $listing" />
                @endforeach
            </div>
            <x-slot:footer>
                <a href="{{ route('safety') }}" target="_blank" class="text-sm font-semibold text-brand-700 hover:underline">Read the safety guidelines</a>
            </x-slot:footer>
        </x-card>
    </fieldset>

    @unless ($locked)
        <x-not-connected :route="$storeRoute" feature="Saving a listing" />

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <x-button :href="route('listings.index')" variant="ghost">Cancel</x-button>
            <x-button type="submit" name="action" value="draft" variant="secondary" :disabled="! $ready">Save as draft</x-button>
            <x-button type="submit" name="action" value="submit" :disabled="! $ready">Submit for review</x-button>
        </div>
    @endunless
</form>
