{{--
    One medicine listing, as a card (grid) or a row (list).
    <x-medicine-card :listing="$listing" />
    <x-medicine-card :listing="$listing" layout="list" />

    $listing is read with array syntax, so it can be a sample array now and an Eloquent model later.
--}}
@props(['listing', 'layout' => 'grid'])

@php
    $isList = $layout === 'list';
    $typeLabel = config('medimatch.listing_types')[$listing['type']] ?? ucfirst($listing['type']);
    $expiry = \Illuminate\Support\Carbon::parse($listing['expiry'].'-01');

    $formIcons = ['Syrup' => 'beaker', 'Suspension' => 'beaker', 'Drops' => 'beaker', 'Sachet' => 'envelope', 'Cream' => 'swatch', 'Inhaler' => 'cloud'];
    $icon = $formIcons[$listing['dosage_form']] ?? 'squares-plus';
@endphp

<article {{ $attributes->class([
    'group relative rounded-card border border-line bg-surface shadow-card transition-shadow hover:shadow-raised focus-within:shadow-raised',
    'flex flex-col p-5' => ! $isList,
    'flex flex-col gap-4 p-5 sm:flex-row sm:items-center' => $isList,
]) }}>
    <div @class(['flex items-start gap-4', 'sm:w-1/3 sm:shrink-0' => $isList])>
        <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-700" aria-hidden="true">
            <x-icon :name="$icon" class="size-6" />
        </span>
        <div class="min-w-0">
            <h3 class="text-base font-semibold">
                <a href="{{ route('medicines.show', $listing['id']) }}" class="rounded-control after:absolute after:inset-0 after:rounded-card">
                    {{ $listing['name'] }} <span class="font-medium text-ink-soft">{{ $listing['strength'] }}</span>
                </a>
            </h3>
            <p class="mt-0.5 truncate text-sm text-ink-muted">{{ $listing['generic_name'] }} · {{ $listing['dosage_form'] }}</p>
        </div>
    </div>

    <div @class(['flex flex-wrap gap-1.5', 'mt-4' => ! $isList, 'sm:w-40 sm:shrink-0' => $isList])>
        <x-badge :tone="$listing['type'] === 'donation' ? 'accent' : 'info'">{{ $typeLabel }}</x-badge>
        @if ($listing['status'] !== 'available')
            <x-status-badge :status="$listing['status']" />
        @endif
        @if ($listing['requires_prescription'])
            <x-badge tone="warning">Prescription check</x-badge>
        @endif
    </div>

    <dl @class([
        'text-sm',
        'mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-line pt-4' => ! $isList,
        'grid min-w-0 flex-1 grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-[6.5rem_5.5rem_minmax(0,1fr)]' => $isList,
    ])>
        <div>
            <dt class="text-xs text-ink-muted">Quantity</dt>
            <dd class="font-medium">{{ $listing['quantity'] }} {{ $listing['unit'] }}</dd>
        </div>
        <div>
            <dt class="text-xs text-ink-muted">Expires</dt>
            <dd class="font-medium">{{ $expiry->format('M Y') }}</dd>
        </div>
        <div @class(['col-span-2 min-w-0', 'sm:col-span-1' => $isList])>
            <dt class="text-xs text-ink-muted">Location</dt>
            <dd class="flex items-start gap-1 font-medium">
                <x-icon name="map-pin" class="mt-0.5 size-4 text-ink-muted" />
                <span @class(['truncate' => ! $isList])>{{ $listing['area'] }}, {{ $listing['district'] }}</span>
            </dd>
        </div>
    </dl>

    @unless ($isList)
        <p class="mt-4 text-xs text-ink-muted">Listed {{ $listing['posted_at']->diffForHumans() }}</p>
    @endunless
</article>
