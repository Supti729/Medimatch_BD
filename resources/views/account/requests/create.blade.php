@php
    $ready = Route::has('requests.store');
    $forms = config('medimatch.dosage_forms');
@endphp

<x-layouts.app title="New request" :preview="$usingSampleData">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Request a medicine" description="Tell us what you need. We will look for matching listings and notify you." />

        <x-alert tone="info" title="Check with your doctor or pharmacist" class="my-6">
            Request only a medicine you have been advised to take. Medimatch BD cannot tell you whether a medicine is right for you.
        </x-alert>

        <form class="space-y-6" @if ($ready) method="POST" action="{{ route('requests.store') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
            @csrf

            <x-card title="Medicine" description="The more exact these details are, the better the matches.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="name" label="Medicine name" :value="$prefill" required maxlength="100" placeholder="Brand or generic, e.g. Napa" />
                    <x-form.input name="generic_name" label="Generic name" optional maxlength="150" placeholder="e.g. Paracetamol" hint="Helps find other brands of the same medicine." />
                    <x-form.input name="strength" label="Strength" required maxlength="50" placeholder="e.g. 500 mg" />
                    <x-form.select name="dosage_form" label="Dosage form" required placeholder="Select form" :options="array_combine($forms, $forms)" />
                    <x-form.input name="quantity" label="Quantity needed" type="number" required min="1" max="1000" inputmode="numeric" />
                    <x-form.input name="unit" label="Unit" required maxlength="30" placeholder="tablets, capsules, bottle…" />
                </div>
            </x-card>

            <x-card title="Where you can collect" description="Matches in your district are shown first. Your address is never asked for.">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.location required with-upazila :division="$user['division']" :district="$user['district']" :upazila="$user['upazila']" />
                </div>
            </x-card>

            <x-card title="Preferences">
                <fieldset>
                    <legend class="text-sm font-medium">Type of assistance</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        @foreach (['either' => ['Either', 'Show me all options.'], 'donation' => ['Donation only', 'Free medicines from donors.'], 'affordable_access' => ['Affordable access', 'From authorized partners.']] as $key => [$label, $text])
                            <label class="flex items-start gap-3 rounded-control border border-line p-4 has-checked:border-brand-300 has-checked:bg-brand-50">
                                <input type="radio" name="preference" value="{{ $key }}" @checked(old('preference', 'either') === $key) class="mt-0.5 size-4 accent-brand-600">
                                <span class="text-sm"><span class="block font-semibold">{{ $label }}</span><span class="text-ink-soft">{{ $text }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="mt-5">
                    <x-form.textarea name="notes" label="Additional information" optional rows="3" maxlength="500"
                        placeholder="For example: can collect on weekends."
                        hint="Do not write your diagnosis, medical history, phone number or address here. Donors can read this text." />
                </div>
            </x-card>

            <x-form.checkbox name="confirm" required
                label="I am requesting this for genuine personal or family need, not to resell, and I understand a prescription check may be required." />

            <x-not-connected route="requests.store" feature="Posting a request" />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-button :href="route('requests.index')" variant="ghost">Cancel</x-button>
                <x-button type="submit" :disabled="! $ready">Post request</x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
