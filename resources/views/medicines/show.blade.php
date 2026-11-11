@php
    $typeLabel = config('medimatch.listing_types')[$listing['type']] ?? ucfirst($listing['type']);
    $expiry = \Illuminate\Support\Carbon::parse($listing['expiry'].'-01');
    $monthsLeft = (int) floor(now()->startOfMonth()->diffInMonths($expiry));
    $canRequest = $listing['status'] === 'available';
    $signInUrl = auth()->check() ? null : route('login');
@endphp

<x-layouts.public :title="$listing['name'].' '.$listing['strength']">
    <div class="page-container py-8">
        <nav aria-label="Breadcrumb" class="text-sm text-ink-muted">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:text-brand-700 hover:underline">Home</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('medicines.index') }}" class="hover:text-brand-700 hover:underline">Browse medicines</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="font-medium text-ink">{{ $listing['name'] }} {{ $listing['strength'] }}</li>
            </ol>
        </nav>

        @if ($usingSampleData)
            <x-sample-notice class="mt-5">This listing is an invented example for the prototype. It is not a real offer of medicine.</x-sample-notice>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-6">
                <x-card>
                    <div class="flex flex-wrap items-start gap-5">
                        <span class="flex size-16 shrink-0 items-center justify-center rounded-card bg-brand-50 text-brand-700" aria-hidden="true">
                            <x-icon name="squares-plus" class="size-8" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap gap-1.5">
                                <x-status-badge :status="$listing['status']" />
                                <x-badge :tone="$listing['type'] === 'donation' ? 'accent' : 'info'">{{ $typeLabel }}</x-badge>
                                @if ($listing['requires_prescription'])
                                    <x-badge tone="warning">Prescription check</x-badge>
                                @endif
                            </div>
                            <h1 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">{{ $listing['name'] }} <span class="text-ink-soft">{{ $listing['strength'] }}</span></h1>
                            <p class="mt-1 text-ink-soft">{{ $listing['generic_name'] }} · {{ $listing['dosage_form'] }}</p>
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-x-6 gap-y-5 border-t border-line pt-6 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            'Generic name' => $listing['generic_name'],
                            'Strength' => $listing['strength'],
                            'Dosage form' => $listing['dosage_form'],
                            'Quantity' => $listing['quantity'].' '.$listing['unit'],
                            'Expiry' => $expiry->format('F Y').($monthsLeft > 0 ? ' ('.$monthsLeft.' '.Str::plural('month', $monthsLeft).' left)' : ''),
                            'Packaging' => $listing['packaging'],
                            'Manufacturer' => $listing['manufacturer'],
                            'Listed' => $listing['posted_at']->format('j M Y'),
                        ] as $term => $value)
                            <div>
                                <dt class="text-ink-muted">{{ $term }}</dt>
                                <dd class="mt-0.5 font-medium">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>

                @if ($listing['requires_prescription'])
                    <x-alert tone="warning" title="A prescription check is needed for this medicine">
                        Before a handover, the requester submits a valid prescription that is reviewed privately by an authorized reviewer.
                        The donor never sees the prescription.
                    </x-alert>
                @endif

                <x-card title="Before you request">
                    <ul class="space-y-3 text-sm text-ink-soft">
                        @foreach ([
                            'Medimatch BD does not check whether this medicine is right for you. Ask a registered doctor or pharmacist before taking any medicine.',
                            'At handover, check that the pack is sealed, the name and strength match, and the expiry date has not passed.',
                            'Do not accept the medicine if the packaging is damaged or looks tampered with, and report the listing.',
                        ] as $point)
                            <li class="flex items-start gap-2.5"><x-icon name="shield-check" class="mt-0.5 size-5 text-brand-600" /> <span>{{ $point }}</span></li>
                        @endforeach
                    </ul>
                    <x-slot:footer>
                        <a href="{{ route('safety') }}" class="text-sm font-semibold text-brand-700 hover:underline">Read the full safety guidelines</a>
                    </x-slot:footer>
                </x-card>
            </div>

            <aside class="space-y-6">
                <x-card>
                    <p class="flex items-center gap-2 text-sm font-medium"><x-icon name="map-pin" class="size-5 text-brand-600" /> {{ $listing['area'] }}, {{ $listing['district'] }}</p>
                    <p class="mt-1 pl-7 text-sm text-ink-muted">{{ $listing['division'] }} Division · area only, exact address is never shown</p>

                    <div class="mt-5 space-y-2.5">
                        @if (! $canRequest)
                            <x-button block disabled>Not available to request</x-button>
                            <p class="text-sm text-ink-muted">This listing is currently {{ str_replace('_', ' ', $listing['status']) }}.</p>
                        @elseif ($signInUrl)
                            <x-button :href="$signInUrl" block icon="lock-closed">Log in to request</x-button>
                            <p class="text-sm text-ink-muted">You need a free account to request a medicine or message a donor.</p>
                        @else
                            <x-button block disabled>Request this medicine</x-button>
                            <p class="text-sm text-ink-muted">Requesting is not connected yet. It is added in the backend part of the project.</p>
                        @endif

                        <div class="grid grid-cols-2 gap-2.5 pt-1">
                            <x-button variant="secondary" icon="bookmark" disabled title="Available after the backend is added">Save</x-button>
                            <x-button variant="secondary" icon="flag" data-modal-open="report-listing">Report</x-button>
                        </div>
                    </div>
                </x-card>

                <x-card title="Offered by">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($listing['donor']['name'], 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $listing['donor']['name'] }}</p>
                            <p class="text-xs text-ink-muted">Member since {{ $listing['donor']['joined']->format('M Y') }}</p>
                        </div>
                        @if ($listing['donor']['verified'])
                            <x-badge tone="success" class="ml-auto">Verified</x-badge>
                        @endif
                    </div>
                    <p class="mt-4 text-xs text-ink-muted">Phone number, email and address are private. Contact happens through in-app messages only.</p>
                </x-card>
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <section class="mt-12" aria-labelledby="similar-heading">
                <h2 id="similar-heading" class="text-xl">Other listings of {{ $listing['generic_name'] }}</h2>
                <p class="mt-1 text-sm text-ink-muted">Same generic medicine. Strength or form may differ, so check before requesting.</p>
                <div class="mt-5 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($similar as $other)
                        <x-medicine-card :listing="$other" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <x-modal id="report-listing" title="Report this listing">
        <p>Reports help moderators remove unsafe or misleading listings.</p>
        <x-not-connected route="reports.store" feature="Reporting" class="mt-4" />
        <form id="report-form" class="mt-4 space-y-4" @if (Route::has('reports.store')) method="POST" action="{{ route('reports.store') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
            @csrf
            <input type="hidden" name="listing_id" value="{{ $listing['id'] }}">
            <x-form.select name="reason" id="report-reason" label="Reason" placeholder="Choose a reason" required
                :options="['unsafe' => 'Unsafe or damaged medicine', 'expired' => 'Expired medicine', 'misleading' => 'Wrong or misleading details', 'selling' => 'Asking for money for a donation', 'other' => 'Something else']" />
            <x-form.textarea name="details" id="report-details" label="Details" rows="3" optional maxlength="500" placeholder="Do not include personal or medical information." />
        </form>
        <x-slot:footer>
            <x-button variant="secondary" data-modal-close>Cancel</x-button>
            <x-button type="submit" form="report-form" :disabled="! Route::has('reports.store')">Send report</x-button>
        </x-slot:footer>
    </x-modal>
</x-layouts.public>
