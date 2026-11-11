@php
    $donateUrl = Route::has('listings.create') ? route('listings.create') : route('register');
    $requestUrl = Route::has('requests.create') ? route('requests.create') : route('register');

    $requesting = [
        ['magnifying-glass', 'Search first', 'Look for the medicine by brand or generic name and filter by your division and district.'],
        ['clipboard-document-list', 'Post a request', 'If nothing fits, post a request with the medicine name, strength, form, quantity and your area.'],
        ['sparkles', 'Receive matches', 'When a suitable listing appears, you are notified. Matches must agree on medicine, strength and form.'],
        ['shield-check', 'Verify if required', 'For prescription medicines, upload your prescription. Only an authorized reviewer sees it.'],
        ['chat-bubble-left-right', 'Arrange the handover', 'Message the donor inside the platform and agree a safe public place to meet.'],
        ['check-circle', 'Confirm completion', 'Both sides confirm the handover, and the listing and request are marked completed.'],
    ];

    $donating = [
        ['clipboard-document-check', 'Check eligibility', 'The medicine must be unopened, in original packaging, not expired, and allowed under the safety guidelines.'],
        ['gift', 'Create a listing', 'Enter the name, strength, form, quantity, expiry date and your area. Add clear photos of the pack.'],
        ['eye', 'Listing review', 'Listings are checked against the rules before they become visible to others.'],
        ['bell', 'Get requests', 'You are notified when someone requests your medicine. You choose who to reserve it for.'],
        ['chat-bubble-left-right', 'Arrange the handover', 'Use in-app messages. Your phone number, email and address stay private.'],
        ['check-circle', 'Confirm completion', 'Mark the handover as completed so the listing closes.'],
    ];
@endphp

<x-layouts.public title="How it works">
    <x-page-hero eyebrow="How it works" title="From a need to a handover"
        description="One free account lets you request medicines and offer the ones you no longer need. Here is what happens at each step." />

    <div class="page-container py-12 sm:py-16">
        <x-alert tone="info" title="Prototype status">
            Medimatch BD is being built in stages. Browsing and search work on sample data today. Accounts, requests, listings,
            messaging and verification are added in the next parts of the project.
        </x-alert>

        <div class="mt-12 grid gap-12 lg:grid-cols-2">
            @foreach ([
                ['If you need a medicine', $requesting, 'brand', $requestUrl, 'Request medicine'],
                ['If you can donate a medicine', $donating, 'accent', $donateUrl, 'Donate medicine'],
            ] as [$heading, $steps, $tone, $url, $cta])
                <section>
                    <h2 class="text-2xl">{{ $heading }}</h2>
                    <ol class="mt-6 space-y-4">
                        @foreach ($steps as $i => [$icon, $title, $text])
                            <li class="flex gap-4 rounded-card border border-line bg-surface p-5 shadow-card">
                                <span @class(['flex size-11 shrink-0 items-center justify-center rounded-control', 'bg-brand-50 text-brand-700' => $tone === 'brand', 'bg-accent-50 text-accent-700' => $tone === 'accent'])>
                                    <x-icon :name="$icon" class="size-6" />
                                </span>
                                <div>
                                    <h3 class="text-base"><span class="text-ink-muted">{{ $i + 1 }}.</span> {{ $title }}</h3>
                                    <p class="mt-1 text-sm text-ink-soft">{{ $text }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    <x-button :href="$url" :variant="$tone === 'accent' ? 'accent' : 'primary'" class="mt-6">{{ $cta }}</x-button>
                </section>
            @endforeach
        </div>

        <section class="mt-16 rounded-card border border-line bg-surface p-6 shadow-card sm:p-8" aria-labelledby="hiw-matching">
            <div class="flex flex-wrap items-center gap-3">
                <h2 id="hiw-matching" class="text-2xl">How matching works</h2>
                <x-badge tone="warning">In development</x-badge>
            </div>
            <div class="mt-5 grid gap-6 md:grid-cols-3">
                <div>
                    <h3 class="text-base">1. Fixed safety rules first</h3>
                    <p class="mt-1.5 text-sm text-ink-soft">The medicine identity, strength and dosage form must agree, and the listing must be eligible and available. Software rules decide this, not AI.</p>
                </div>
                <div>
                    <h3 class="text-base">2. AI ranks what is left</h3>
                    <p class="mt-1.5 text-sm text-ink-soft">A language model compares the meaning of the request and listing text, so different spellings and brand or generic names can still be found.</p>
                </div>
                <div>
                    <h3 class="text-base">3. People decide</h3>
                    <p class="mt-1.5 text-sm text-ink-soft">You see a ranked list and choose. The AI never diagnoses, prescribes, approves prescriptions or says a medicine is suitable for you.</p>
                </div>
            </div>
        </section>

        <section class="mt-16" aria-labelledby="hiw-faq">
            <h2 id="hiw-faq" class="text-2xl">Common questions</h2>
            <div class="mt-6 divide-y divide-line rounded-card border border-line bg-surface shadow-card">
                @foreach ([
                    ['Does it cost anything?', 'No. Finding, requesting and donating medicines is free for individuals.'],
                    ['Can I sell medicines here?', 'No. Individuals can only donate. “Affordable access” listings are reserved for authorized partner organizations and only where the law allows it.'],
                    ['Who can see my phone number or address?', 'Nobody. Listings show only your area (upazila and district), and contact happens through in-app messages.'],
                    ['Which medicines can be listed?', 'Only unopened, unexpired medicines in original packaging. Controlled drugs, medicines needing refrigeration and opened packs cannot be listed. See the safety guidelines.'],
                    ['Is this a real service?', 'Not yet. This is an academic prototype. A real service would need approval under Bangladesh medicine regulations.'],
                ] as [$question, $answer])
                    <details class="group p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-semibold [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            <x-icon name="chevron-down" class="size-5 text-ink-muted transition-transform group-open:rotate-180" />
                        </summary>
                        <p class="mt-3 text-sm text-ink-soft">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.public>
