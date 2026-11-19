@php $ready = Route::has('admin.settings.update'); @endphp

<x-layouts.admin title="Settings" preview>
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Admin settings" description="Platform rules for listings, requests and verification. Changes are recorded in the audit log." />

        <form class="mt-6 space-y-6" @if ($ready) method="POST" action="{{ route('admin.settings.update') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
            @csrf
            @if ($ready) @method('PUT') @endif

            <x-card title="Listing rules">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.input name="min_shelf_life_months" label="Minimum shelf life (months)" type="number" min="1" max="24" :value="$settings['min_shelf_life_months']" required />
                    <x-form.input name="max_quantity" label="Maximum quantity" type="number" min="1" max="10000" :value="$settings['max_quantity']" required />
                    <x-form.input name="max_photos" label="Photos per listing" type="number" min="1" max="10" :value="$settings['max_photos']" required />
                </div>
                <div class="mt-5 space-y-4">
                    <x-form.checkbox name="review_listings" label="New listings need a moderator's approval" :checked="$settings['review_listings']" hint="Recommended. When off, listings appear immediately." />
                    <x-form.checkbox name="allow_affordable_access" label="Allow affordable-access listings by verified partners" :checked="$settings['allow_affordable_access']"
                        hint="Keep this off until partner licensing and the legal basis are confirmed." />
                </div>
            </x-card>

            <x-card title="Requests and verification">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="request_expiry_days" label="Close unanswered requests after (days)" type="number" min="7" max="365" :value="$settings['request_expiry_days']" required />
                </div>
                <div class="mt-5">
                    <x-form.checkbox name="prescription_check" label="Prescription medicines need a reviewed document before a handover" :checked="$settings['prescription_check']" />
                </div>
            </x-card>

            <x-card title="Roles">
                <ul class="space-y-3 text-sm">
                    @foreach ([
                        ['Administrator', 'Manages users, listings, reports, partners and settings.'],
                        ['Authorized reviewer', 'Reviews verification documents. Cannot change settings.'],
                        ['Partner', 'A verified organization. May offer affordable-access listings when allowed.'],
                        ['User', 'Can request and donate medicines.'],
                    ] as [$role, $text])
                        <li class="flex flex-wrap gap-x-3"><span class="w-40 font-semibold">{{ $role }}</span><span class="flex-1 text-ink-soft">{{ $text }}</span></li>
                    @endforeach
                </ul>
                <x-slot:footer><p class="text-xs text-ink-muted">Roles are assigned by an administrator on the server. Users can never choose their own role.</p></x-slot:footer>
            </x-card>

            <x-not-connected route="admin.settings.update" feature="Saving settings" />

            <div class="flex justify-end">
                <x-button type="submit" :disabled="! $ready">Save settings</x-button>
            </div>
        </form>
    </div>
</x-layouts.admin>
