@php
    $donateUrl = Route::has('listings.create') ? route('listings.create') : route('register');
    $requestUrl = Route::has('requests.create') ? route('requests.create') : route('register');
    $divisions = array_keys(config('bangladesh.divisions'));
@endphp

<x-layouts.public>
    {{-- Hero --}}
    <section class="border-b border-line bg-surface">
        <div class="page-container grid items-center gap-12 py-12 sm:py-16 lg:grid-cols-[1.15fr_1fr] lg:py-20">
            <div>
                <x-badge tone="brand" dot>Medicine assistance for Bangladesh</x-badge>

                <h1 class="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">
                    Find the medicine you need.
                    <span class="text-brand-600">Share what someone else needs.</span>
                </h1>

                <p class="mt-5 max-w-xl text-lg text-ink-soft">
                    Medimatch BD connects people who need eligible medicines with donors and authorized partners
                    near them, privately and free of charge.
                </p>

                <form method="GET" action="{{ route('medicines.index') }}" role="search"
                    class="mt-8 grid gap-3 rounded-card border border-line bg-canvas p-3 sm:grid-cols-[1fr_11rem_auto]">
                    <x-form.input name="q" aria-label="Medicine name" placeholder="Medicine name" icon="magnifying-glass" maxlength="80" />
                    <x-form.select name="division" aria-label="Division" placeholder="All divisions" :options="array_combine($divisions, $divisions)" />
                    <x-button type="submit" icon="magnifying-glass">Find medicine</x-button>
                </form>

                <div class="mt-5 flex flex-wrap gap-3">
                    <x-button :href="$donateUrl" variant="accent" icon="gift">Donate medicine</x-button>
                    <x-button :href="$requestUrl" variant="secondary" icon="clipboard-document-list">Request medicine</x-button>
                </div>

                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink-soft">
                    <li class="flex items-center gap-2"><x-icon name="lock-closed" class="size-4 text-brand-600" /> Phone and email stay private</li>
                    <li class="flex items-center gap-2"><x-icon name="map-pin" class="size-4 text-brand-600" /> Search by division and district</li>
                    <li class="flex items-center gap-2"><x-icon name="heart" class="size-4 text-brand-600" /> Free for people who need help</li>
                </ul>
            </div>

            {{-- Illustration: how a request meets a listing --}}
            <div class="relative mx-auto w-full max-w-md lg:max-w-none" aria-hidden="true">
                <div class="rounded-card border border-line bg-canvas p-5 sm:p-6">
                    <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Example of a match</p>

                    <div class="mt-4 rounded-card border border-line bg-surface p-4 shadow-card">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-full bg-info-50 text-info-700"><x-icon name="clipboard-document-list" class="size-5" /></span>
                            <div>
                                <p class="text-xs text-ink-muted">Someone requests</p>
                                <p class="text-sm font-semibold">“Paracetamol 500 mg tablets”</p>
                            </div>
                        </div>
                    </div>

                    <div class="my-3 flex items-center justify-center gap-2 text-xs font-semibold text-brand-700">
                        <span class="h-px flex-1 bg-line"></span>
                        <x-icon name="sparkles" class="size-4" /> Matched by meaning, not just spelling
                        <span class="h-px flex-1 bg-line"></span>
                    </div>

                    <div class="rounded-card border border-brand-200 bg-surface p-4 shadow-raised">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-full bg-accent-50 text-accent-700"><x-icon name="gift" class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-ink-muted">A donor nearby has listed</p>
                                <p class="text-sm font-semibold">“Napa 500 mg Tablet”</p>
                            </div>
                            <x-badge tone="success" dot>Available</x-badge>
                        </div>
                        <p class="mt-3 flex items-center gap-1.5 text-xs text-ink-muted"><x-icon name="map-pin" class="size-4" /> Same district · strength and form checked separately</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="page-container py-16 sm:py-20" aria-labelledby="home-how">
        <x-section-heading id="home-how" eyebrow="How it works" title="Three steps from need to help" center
            description="One account lets you both request medicines and offer the ones you no longer need." />

        <ol class="mt-10 grid gap-5 md:grid-cols-3">
            @foreach ([
                ['magnifying-glass', 'Search or post', 'Search listings near you, post a request for what you need, or list an eligible medicine you can give.'],
                ['sparkles', 'Get matched', 'Requests and listings are matched by medicine, strength, form and location, with AI helping to rank the closest ones.'],
                ['chat-bubble-left-right', 'Coordinate safely', 'Agree the handover through private in-app messages. Your phone number and email are never shown.'],
            ] as $i => [$icon, $title, $text])
                <li class="rounded-card border border-line bg-surface p-6 shadow-card">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-control bg-brand-50 text-brand-700"><x-icon :name="$icon" class="size-6" /></span>
                        <span class="text-sm font-semibold text-ink-muted">Step {{ $i + 1 }}</span>
                    </div>
                    <h3 class="mt-4 text-lg">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink-soft">{{ $text }}</p>
                </li>
            @endforeach
        </ol>

        <p class="mt-8 text-center">
            <a href="{{ route('how-it-works') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                See the full process <x-icon name="arrow-right" class="size-4" />
            </a>
        </p>
    </section>

    {{-- Donate / Request --}}
    <section class="border-y border-line bg-surface" aria-label="Donate or request">
        <div class="page-container grid gap-6 py-16 sm:py-20 lg:grid-cols-2">
            <div class="rounded-card border border-accent-200 bg-accent-50 p-6 sm:p-8">
                <span class="flex size-12 items-center justify-center rounded-control bg-surface text-accent-600 shadow-card"><x-icon name="gift" class="size-6" /></span>
                <h2 class="mt-5 text-2xl">Have unused medicine?</h2>
                <p class="mt-2 text-ink-soft">
                    Sealed, unexpired medicines that you no longer need can help someone nearby instead of going to waste.
                </p>
                <ul class="mt-5 space-y-2.5 text-sm text-ink-soft">
                    @foreach (['Only unopened medicines in original packaging', 'Clear expiry date with enough shelf life left', 'Listings are reviewed before they appear'] as $point)
                        <li class="flex items-start gap-2.5"><x-icon name="check-circle" class="mt-0.5 size-5 text-accent-600" /> {{ $point }}</li>
                    @endforeach
                </ul>
                <div class="mt-6 flex flex-wrap gap-3">
                    <x-button :href="$donateUrl" variant="accent">Donate medicine</x-button>
                    <x-button :href="route('safety')" variant="ghost">What can be listed?</x-button>
                </div>
            </div>

            <div class="rounded-card border border-brand-200 bg-brand-50 p-6 sm:p-8">
                <span class="flex size-12 items-center justify-center rounded-control bg-surface text-brand-700 shadow-card"><x-icon name="clipboard-document-list" class="size-6" /></span>
                <h2 class="mt-5 text-2xl">Need a medicine?</h2>
                <p class="mt-2 text-ink-soft">
                    Search current listings, or post a request and be notified when a matching medicine is listed near you.
                </p>
                <ul class="mt-5 space-y-2.5 text-sm text-ink-soft">
                    @foreach (['Free for people who need help', 'Share only what is needed, nothing more', 'Prescription medicines need a verification check'] as $point)
                        <li class="flex items-start gap-2.5"><x-icon name="check-circle" class="mt-0.5 size-5 text-brand-600" /> {{ $point }}</li>
                    @endforeach
                </ul>
                <div class="mt-6 flex flex-wrap gap-3">
                    <x-button :href="$requestUrl">Request medicine</x-button>
                    <x-button :href="route('medicines.index')" variant="ghost">Browse listings</x-button>
                </div>
            </div>
        </div>
    </section>

    {{-- Location + AI --}}
    <section class="page-container grid gap-6 py-16 sm:py-20 lg:grid-cols-2" aria-label="Location search and AI matching">
        <div class="rounded-card border border-line bg-surface p-6 shadow-card sm:p-8">
            <span class="flex size-12 items-center justify-center rounded-control bg-info-50 text-info-700"><x-icon name="map-pin" class="size-6" /></span>
            <h2 class="mt-5 text-2xl">Help that is close to you</h2>
            <p class="mt-2 text-ink-soft">
                Filter by division, district and upazila to find medicines you can realistically collect.
                Listings show the area only, never a home address.
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach ($divisions as $division)
                    <a href="{{ route('medicines.index', ['division' => $division]) }}"
                        class="rounded-full border border-line bg-canvas px-3 py-1.5 text-sm font-medium text-ink-soft hover:border-brand-300 hover:bg-brand-50 hover:text-brand-800">{{ $division }}</a>
                @endforeach
            </div>
        </div>

        <div class="rounded-card border border-line bg-surface p-6 shadow-card sm:p-8">
            <div class="flex items-center justify-between gap-3">
                <span class="flex size-12 items-center justify-center rounded-control bg-brand-50 text-brand-700"><x-icon name="sparkles" class="size-6" /></span>
                <x-badge tone="warning">In development</x-badge>
            </div>
            <h2 class="mt-5 text-2xl">AI-assisted matching</h2>
            <p class="mt-2 text-ink-soft">
                People describe the same medicine in different ways. Our matching service compares meaning, so a request for
                “paracetamol 500 mg” can find a listing written as “Napa 500 mg Tablet”.
            </p>
            <div class="mt-5 rounded-control border border-line bg-canvas p-4 text-sm text-ink-soft">
                <p class="font-semibold text-ink">What the AI does not do</p>
                <p class="mt-1">
                    It never diagnoses, prescribes, or decides whether a medicine is suitable for you. Strength, dosage form
                    and eligibility are checked by fixed rules, and medical decisions stay with your doctor or pharmacist.
                </p>
            </div>
        </div>
    </section>

    {{-- Trust and safety --}}
    <section class="border-y border-line bg-surface" aria-labelledby="home-trust">
        <div class="page-container py-16 sm:py-20">
            <x-section-heading id="home-trust" eyebrow="Trust and safety" title="Built to be careful with medicines and with your data" />

            <div class="mt-10 grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['eye-slash', 'Private by default', 'Public profiles show a name and join date. Phone, email and address are never public.'],
                    ['shield-check', 'Verification where needed', 'Prescription medicines need a document check by an authorized reviewer, stored privately.'],
                    ['clipboard-document-check', 'Reviewed listings', 'Listings follow eligibility rules and can be blocked by moderators if they are unsafe.'],
                    ['flag', 'Report anything', 'Every listing and message can be reported. Reports are reviewed and actions are logged.'],
                ] as [$icon, $title, $text])
                    <div>
                        <span class="flex size-11 items-center justify-center rounded-control bg-brand-50 text-brand-700"><x-icon :name="$icon" class="size-6" /></span>
                        <h3 class="mt-4 text-base">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm text-ink-soft">{{ $text }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mt-10">
                <a href="{{ route('safety') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline">
                    Read the safety guidelines <x-icon name="arrow-right" class="size-4" />
                </a>
            </p>
        </div>
    </section>

    {{-- Latest listings --}}
    <section class="page-container py-16 sm:py-20" aria-labelledby="home-latest">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-section-heading id="home-latest" eyebrow="Listings" title="Recently listed medicines" />
            <x-button :href="route('medicines.index')" variant="secondary" icon-right="arrow-right">Browse all</x-button>
        </div>

        @if ($usingSampleData)
            <x-sample-notice class="mt-6" />
        @endif

        @if ($latestListings->isEmpty())
            <x-card class="mt-6" :padded="false">
                <x-empty-state icon="gift" title="No listings yet" description="Be the first to offer a medicine you no longer need.">
                    <x-button :href="$donateUrl" variant="accent">Donate medicine</x-button>
                </x-empty-state>
            </x-card>
        @else
            <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($latestListings as $listing)
                    <x-medicine-card :listing="$listing" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Community impact --}}
    <section class="border-y border-line bg-surface" aria-labelledby="home-impact">
        <div class="page-container py-16 sm:py-20">
            <x-section-heading id="home-impact" eyebrow="Community impact" title="Less medicine wasted, more people helped"
                description="Unused medicines often expire in a drawer while someone nearby struggles to find or afford the same thing." />

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card label="Medicines listed" :value="$stats['listings']" icon="gift" tone="accent" />
                <x-stat-card label="Requests posted" :value="$stats['requests']" icon="clipboard-document-list" tone="info" />
                <x-stat-card label="Handovers completed" :value="$stats['completed']" icon="check-circle" tone="success" />
                <x-stat-card label="Districts reached" :value="$stats['districts']" icon="map-pin" />
            </div>

            @if (collect($stats)->filter(fn ($value) => $value !== null)->isEmpty())
                <p class="mt-4 text-sm text-ink-muted">
                    These numbers will be counted live from the database. The prototype has no real activity yet, so no figures are shown.
                </p>
            @endif
        </div>
    </section>

    {{-- Closing call to action --}}
    <section class="page-container py-16 sm:py-20" aria-labelledby="home-cta">
        <div class="rounded-card bg-brand-900 px-6 py-12 text-center sm:px-12">
            <h2 id="home-cta" class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">One account. Request or donate.</h2>
            <p class="mx-auto mt-3 max-w-xl text-brand-100">
                Create a free account to post requests, list medicines and message privately.
            </p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <x-button :href="route('register')" size="lg" class="!bg-white !text-brand-900 hover:!bg-brand-50">Create account</x-button>
                <x-button :href="route('medicines.index')" size="lg" variant="ghost" class="!text-white ring-1 ring-white/30 ring-inset hover:bg-white/10 hover:!text-white">Browse medicines</x-button>
            </div>
        </div>
    </section>
</x-layouts.public>
