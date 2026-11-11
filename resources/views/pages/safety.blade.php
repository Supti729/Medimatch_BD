<x-layouts.public title="Safety guidelines">
    <x-page-hero eyebrow="Safety guidelines" title="Sharing medicines carefully"
        description="These rules protect the people who receive medicines. Listings that break them are removed." />

    <div class="page-container py-12 sm:py-16">
        <x-alert tone="warning" title="Prototype rules, not legal advice">
            These guidelines describe how the academic prototype is designed. A real service would have to follow the rules of the
            Directorate General of Drug Administration (DGDA) and other applicable Bangladesh law, and these rules would be revised to match.
        </x-alert>

        <div class="mt-10 grid gap-6 lg:grid-cols-2">
            <x-card title="Can be listed">
                <ul class="space-y-3 text-sm text-ink-soft">
                    @foreach ([
                        'Unopened medicines in their original, sealed packaging',
                        'A clearly readable name, strength, batch number and expiry date',
                        'Enough shelf life left before the expiry date',
                        'Medicines that have been stored as the pack instructs (cool, dry place)',
                        'Prescription medicines, only with a verification check on the requester',
                    ] as $item)
                        <li class="flex items-start gap-2.5"><x-icon name="check-circle" class="mt-0.5 size-5 text-success-600" /> <span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </x-card>

            <x-card title="Cannot be listed">
                <ul class="space-y-3 text-sm text-ink-soft">
                    @foreach ([
                        'Expired medicines, or medicines about to expire',
                        'Opened, loose, repackaged or damaged packs',
                        'Narcotic, psychotropic and other controlled medicines',
                        'Medicines that need refrigeration, such as insulin and many vaccines',
                        'Injections, physician samples marked “not for sale”, and anything without a label',
                        'Medicines offered for sale by individuals',
                    ] as $item)
                        <li class="flex items-start gap-2.5"><x-icon name="x-circle" class="mt-0.5 size-5 text-danger-600" /> <span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </x-card>
        </div>

        <div class="mt-12 grid gap-10 lg:grid-cols-3">
            @foreach ([
                ['Before you take a donated medicine', 'heart', [
                    'Ask a registered doctor or pharmacist whether the medicine is right for you.',
                    'Never change your dose or swap one medicine for another because of a listing.',
                    'Check the name, strength and expiry date on the pack yourself.',
                    'Do not use it if the seal is broken or the pack looks tampered with.',
                ]],
                ['Meeting for a handover', 'map-pin', [
                    'Meet in a public place in daylight, such as outside a pharmacy or hospital.',
                    'Tell someone you trust where you are going.',
                    'Do not share your home address, and do not pay for a donation.',
                    'Keep all arrangements inside in-app messages.',
                ]],
                ['Your privacy', 'eye-slash', [
                    'Your phone number, email and address are never shown to other users.',
                    'Prescriptions are seen only by authorized reviewers, never by donors.',
                    'Do not put health details in listings, requests or messages.',
                    'Report anyone who asks for personal or payment information.',
                ]],
            ] as [$heading, $icon, $points])
                <section>
                    <span class="flex size-11 items-center justify-center rounded-control bg-brand-50 text-brand-700"><x-icon :name="$icon" class="size-6" /></span>
                    <h2 class="mt-4 text-xl">{{ $heading }}</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-ink-soft">
                        @foreach ($points as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        <x-alert tone="danger" title="In an emergency" class="mt-12">
            Medimatch BD is not an emergency service. If someone is seriously unwell, go to the nearest hospital or call the
            national emergency number 999.
        </x-alert>

        <section class="mt-12 rounded-card border border-line bg-surface p-6 shadow-card sm:p-8">
            <h2 class="text-xl">See something unsafe?</h2>
            <p class="mt-2 max-w-2xl text-sm text-ink-soft">
                Use the Report button on any listing. Moderators review reports, can block listings and suspend accounts, and every action is recorded.
            </p>
            <div class="mt-5 flex flex-wrap gap-3">
                <x-button :href="route('medicines.index')" variant="secondary">Browse medicines</x-button>
                <x-button :href="route('contact')" variant="ghost">Contact the team</x-button>
            </div>
        </section>
    </div>
</x-layouts.public>
