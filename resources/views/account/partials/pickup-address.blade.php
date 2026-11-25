{{--
    The donor's exact pick-up address. Include ONLY for the requester of an accepted reservation
    (or the listing owner): the address is never shown publicly.
    $listing: MedicineListing
--}}
<div class="mt-4 flex items-start gap-3 rounded-control border border-brand-200 bg-brand-50 p-3 text-sm">
    <x-icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-brand-700" />
    <div class="min-w-0">
        <p class="font-semibold text-ink">Pick-up address</p>
        @if (filled($listing->pickup_address))
            <p class="break-words whitespace-pre-line text-ink-soft">{{ $listing->pickup_address }}</p>
        @else
            <p class="text-ink-soft">The donor has not added an address. Ask them in your messages.</p>
        @endif
        <p class="mt-1 text-xs text-ink-muted">Shown to you because the donor accepted your request. Meet in a safe, public place if you prefer.</p>
    </div>
</div>
