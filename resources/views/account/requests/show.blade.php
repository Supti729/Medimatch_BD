@php
    $r = $medicineRequest;
    // The prescription step only exists for prescription medicines.
    $steps = $r->medicine->requires_prescription
        ? ['active' => 'Posted', 'verification_pending' => 'Prescription check', 'reserved' => 'Reserved', 'completed' => 'Completed']
        : ['active' => 'Posted', 'reserved' => 'Reserved', 'completed' => 'Completed'];
    $order = array_keys($steps);
    $current = array_search($r->status->value, $order, true);
    $preferences = ['either' => 'Donation or affordable access', 'donation' => 'Donation only', 'affordable_access' => 'Affordable access'];
    $activeReservation = $r->reservations->first(fn ($res) => $res->status->isActive());
    $completedReservation = $r->reservations->first(fn ($res) => $res->status->value === 'completed');
@endphp

<x-layouts.app :title="'Request: '.$r->medicine->generic_name">
    <a href="{{ route('requests.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700">
        <x-icon name="arrow-left" class="size-4" /> My requests
    </a>

    <x-page-header class="mt-3" :title="$r->medicine->generic_name.' '.$r->medicine->strength" :description="$r->medicine->dosage_form.' · '.$r->quantity.' '.$r->unit">
        <x-status-badge :status="$r->status" />
        @can('update', $r)
            <x-button :href="route('requests.edit', $r)" variant="secondary" size="sm" icon="pencil-square">Edit</x-button>
        @endcan
        @can('cancel', $r)
            <x-button variant="secondary" size="sm" data-modal-open="cancel-request">Cancel request</x-button>
        @endcan
    </x-page-header>

    {{-- Progress --}}
    @if ($current !== false)
        <x-card class="mt-6">
            <ol @class(['grid gap-4', 'sm:grid-cols-4' => count($steps) === 4, 'sm:grid-cols-3' => count($steps) === 3]) aria-label="Request progress">
                @foreach ($steps as $key => $label)
                    @php $i = array_search($key, $order, true); $done = $i < $current; $now = $i === $current; @endphp
                    <li class="flex items-center gap-3 sm:flex-col sm:items-start sm:gap-2" @if ($now) aria-current="step" @endif>
                        <span @class(['flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold', 'bg-brand-600 text-white' => $done || $now, 'bg-canvas text-ink-muted ring-1 ring-line ring-inset' => ! $done && ! $now])>
                            @if ($done) <x-icon name="check" class="size-4" /> @else {{ $i + 1 }} @endif
                        </span>
                        <span @class(['text-sm', 'font-semibold text-ink' => $now, 'text-ink-soft' => $done, 'text-ink-muted' => ! $done && ! $now])>
                            {{ $label }} @if ($now) <span class="sr-only">(current step)</span> @endif
                        </span>
                    </li>
                @endforeach
            </ol>
        </x-card>
    @else
        <x-alert tone="info" class="mt-6">This request is {{ str_replace('_', ' ', $r->status->value) }} and is no longer being matched.</x-alert>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            @error('reservation') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

            @if ($r->status->value === 'verification_pending')
                <x-alert tone="warning" title="A prescription check is needed">
                    A donor reserved this prescription medicine for you. The handover can happen once an authorized reviewer has checked your prescription.
                    Uploading prescriptions is added in a later part of the project.
                </x-alert>
            @endif

            @if ($completedReservation)
                <x-card title="Fulfilled">
                    <p class="flex items-start gap-2.5 text-sm text-ink-soft">
                        <x-icon name="check-circle" class="mt-0.5 size-5 text-success-600" />
                        <span>You received {{ $completedReservation->listing->brand_name }} {{ $completedReservation->listing->medicine->strength }}
                            on {{ $completedReservation->completed_at->format('j M Y') }}. It is kept in your
                            <a href="{{ route('history.index') }}" class="font-semibold text-brand-700 hover:underline">history</a>.</span>
                    </p>
                </x-card>
            @endif

            @if ($activeReservation)
                <x-card title="Your reservation">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">{{ $activeReservation->listing->brand_name }} {{ $activeReservation->listing->medicine->strength }}</p>
                            <p class="text-sm text-ink-muted">{{ $activeReservation->status->label() }} · requested {{ $activeReservation->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-button :href="route('medicines.show', $activeReservation->medicine_listing_id)" variant="secondary" size="sm">View listing</x-button>
                            @if ($activeReservation->status->value === 'accepted' && $r->status->value === 'reserved')
                                <form method="POST" action="{{ route('reservations.complete', $activeReservation) }}" data-loading>@csrf @method('PATCH')
                                    <x-button type="submit" size="sm" icon="check">I received it</x-button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('reservations.cancel', $activeReservation) }}" data-loading>@csrf @method('PATCH')
                                <x-button type="submit" variant="ghost" size="sm">Cancel</x-button>
                            </form>
                        </div>
                    </div>
                </x-card>
            @endif

            @if ($r->status->value === 'active')
                <x-card title="Matching listings" description="Same medicine, strength and form, available now. Your district is shown first." :padded="false">
                    @if ($matches->isEmpty())
                        <x-empty-state icon="sparkles" title="No matching listing yet" description="Check back later. New listings for this medicine will appear here.">
                            <x-button :href="route('medicines.index', ['q' => $r->medicine->generic_name])" variant="secondary" size="sm">Search listings yourself</x-button>
                        </x-empty-state>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($matches as $listing)
                                <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:p-6">
                                    <x-medicine-card :listing="$listing->toCard()" layout="list" class="flex-1 shadow-none" />
                                    <div class="shrink-0">
                                        @if (in_array($listing->id, $requestedListingIds, true))
                                            <x-badge tone="info">Requested</x-badge>
                                        @else
                                            <form method="POST" action="{{ route('reservations.store', $listing) }}" data-loading>@csrf
                                                <x-button type="submit" size="sm" icon="hand-raised">Request</x-button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <x-slot:footer>
                        <p class="flex items-start gap-2 text-xs text-ink-muted">
                            <x-icon name="information-circle" class="size-4" />
                            <span>A match means the listing is for the same medicine. It is not advice that the medicine is suitable for you. AI ranking is added in a later part of the project; this list uses fixed rules.</span>
                        </p>
                    </x-slot:footer>
                </x-card>
            @endif
        </div>

        <aside>
            <x-card title="Request details">
                <x-detail-list :columns="1" :items="[
                    'Medicine' => $r->medicine->label(),
                    'Quantity needed' => $r->quantity.' '.$r->unit,
                    'Area' => $r->upazila->name.', '.$r->district->name,
                    'Assistance' => $preferences[$r->preference] ?? $r->preference,
                    'Additional information' => $r->notes ?? 'None',
                    'Requested on' => $r->created_at->format('j M Y'),
                    'Open until' => $r->status->isOpen() ? $r->expires_at?->format('j M Y') : null,
                ]" />
            </x-card>
        </aside>
    </div>

    @can('cancel', $r)
        <x-confirm-modal id="cancel-request" title="Cancel this request?" :action="route('requests.cancel', $r)" method="PATCH" confirm="Yes, cancel request" tone="danger">
            Matching stops, and any medicine reserved for you is released for someone else.
        </x-confirm-modal>
    @endcan
</x-layouts.app>
