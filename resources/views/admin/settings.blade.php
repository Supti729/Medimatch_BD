<x-layouts.admin title="Settings">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Admin settings" description="Platform rules for listings and requests. Every change is recorded in the audit log." />

        <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 space-y-6" data-loading>
            @csrf
            @method('PUT')

            <x-card title="Listing rules">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.input name="min_shelf_life_months" label="Minimum shelf life (months)" type="number" min="1" max="24" :value="$settings['min_shelf_life_months']" required />
                    <x-form.input name="max_quantity" label="Maximum quantity" type="number" min="1" max="10000" :value="$settings['max_quantity']" required />
                    <x-form.input name="max_photos" label="Photos per listing" type="number" min="1" max="10" :value="$settings['max_photos']" required />
                </div>
                <div class="mt-5">
                    <x-form.checkbox name="allow_affordable_access" label="Allow affordable-access listings by partner organizations" :checked="$settings['allow_affordable_access']"
                        hint="Keep this off until partner licensing and the legal basis are confirmed." />
                </div>
            </x-card>

            <x-card title="Requests">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="request_expiry_days" label="Close unanswered requests after (days)" type="number" min="7" max="365" :value="$settings['request_expiry_days']" required hint="Applies to requests posted from now on." />
                </div>
            </x-card>

            <x-card title="Always on" description="These safety rules cannot be switched off.">
                <ul class="space-y-2.5 text-sm text-ink-soft">
                    @foreach (['Every new or changed listing is reviewed by a moderator before it becomes visible.', 'Prescription medicines are handed over only after an authorized reviewer accepts the prescription.', 'Restricted medicines in the catalogue can never be listed.'] as $rule)
                        <li class="flex items-start gap-2.5"><x-icon name="lock-closed" class="mt-0.5 size-5 text-brand-600" /> {{ $rule }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-card title="Roles">
                <ul class="space-y-3 text-sm">
                    @foreach ([
                        ['Administrator', 'Manages users, listings, reports, the catalogue, partners and settings.'],
                        ['Authorized reviewer', 'Reviews prescription documents. Cannot change settings.'],
                        ['Partner', 'A verified organization. May offer affordable-access listings when allowed.'],
                        ['User', 'Requests and donates medicines.'],
                    ] as [$role, $text])
                        <li class="flex flex-wrap gap-x-3"><span class="w-40 font-semibold">{{ $role }}</span><span class="flex-1 text-ink-soft">{{ $text }}</span></li>
                    @endforeach
                </ul>
                <x-slot:footer><p class="text-xs text-ink-muted">Roles are changed on a user's page. Administrator access can only be given on the server, never from the panel.</p></x-slot:footer>
            </x-card>

            <div class="flex justify-end">
                <x-button type="submit">Save settings</x-button>
            </div>
        </form>
    </div>
</x-layouts.admin>
