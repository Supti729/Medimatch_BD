@php
    $packaging = config('medimatch.packaging')[$listing->packaging] ?? $listing->packaging;
    $months = $listing->monthsUntilExpiry();
@endphp

<x-layouts.app :title="($locked ? 'Listing: ' : 'Edit listing: ').$listing->brand_name">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('listings.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700">
            <x-icon name="arrow-left" class="size-4" /> My listings
        </a>

        <x-page-header class="mt-3" :title="$listing->brand_name.' '.$listing->medicine->strength" :description="$listing->medicine->generic_name.' · '.$listing->medicine->dosage_form">
            <x-status-badge :status="$listing->status" />
        </x-page-header>

        <div class="my-6 space-y-4">
            @if ($listing->review_note)
                <x-alert :tone="$listing->status->value === 'rejected' ? 'danger' : 'info'" title="Note from the moderator">{{ $listing->review_note }}</x-alert>
            @endif
            @if ($listing->status->value === 'pending_review')
                <x-alert tone="info" title="Waiting for review">A moderator checks every listing before it becomes visible. To change it now, withdraw it from review first.</x-alert>
            @endif
        </div>

        {{-- Status actions --}}
        @if ($actions || $listing->status->isDeletable())
            <x-card title="Listing status" class="mb-6">
                <div class="flex flex-wrap gap-2">
                    @foreach ($actions as $action)
                        @if ($action->value === 'cancel')
                            <x-button variant="secondary" size="sm" data-modal-open="cancel-listing" icon="x-circle">{{ $action->label() }}</x-button>
                        @else
                            <form method="POST" action="{{ route('listings.status', $listing) }}" data-loading>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="{{ $action->value }}">
                                <x-button type="submit" size="sm" :variant="$action->value === 'submit' ? 'primary' : 'secondary'">{{ $action->label() }}</x-button>
                            </form>
                        @endif
                    @endforeach
                    @if ($listing->status->isDeletable())
                        <x-button variant="danger" size="sm" data-modal-open="delete-listing" icon="trash">Delete</x-button>
                    @endif
                </div>
            </x-card>
        @endif

        {{-- Requests from other people --}}
        @if ($listing->reservations->isNotEmpty())
            @php $pending = $listing->reservations->where('status', \App\Enums\ReservationStatus::Pending); @endphp
            <x-card title="Requests for this listing" :description="$pending->count() ? $pending->count().' waiting for your answer. Accepting one declines the others.' : null" :padded="false" class="mb-6">
                @error('reservation') <div class="p-5 pb-0"><x-alert tone="danger">{{ $message }}</x-alert></div> @enderror
                <ul class="divide-y divide-line">
                    @foreach ($listing->reservations as $reservation)
                        <li class="flex flex-wrap items-start gap-3 px-5 py-4 sm:px-6">
                            <x-avatar :user="$reservation->requester" size="sm" public />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold"><a href="{{ route('people.show', $reservation->requester) }}" class="hover:text-brand-700 hover:underline">{{ $reservation->requester->name }}</a></p>
                                <p class="text-xs text-ink-muted">{{ $reservation->created_at->diffForHumans() }}{{ $reservation->request ? ' · needs '.$reservation->request->quantity.' '.$reservation->request->unit : '' }}</p>
                                @if ($reservation->message)
                                    <p class="mt-2 rounded-control bg-canvas px-3 py-2 text-sm text-ink-soft">“{{ $reservation->message }}”</p>
                                @endif
                                @if ($reservation->status->value === 'accepted' && $reservation->request?->status->value === 'verification_pending')
                                    <p class="mt-2 text-xs text-warning-700">Waiting for the requester's prescription check before the handover.</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($reservation->conversation)
                                    <x-button :href="route('messages.show', $reservation->conversation)" size="sm" variant="secondary" icon="chat-bubble-left-right">Message</x-button>
                                @endif
                                @switch($reservation->status->value)
                                    @case('pending')
                                        <form method="POST" action="{{ route('reservations.accept', $reservation) }}" data-loading>@csrf @method('PATCH')
                                            <x-button type="submit" size="sm" icon="check">Accept</x-button>
                                        </form>
                                        <form method="POST" action="{{ route('reservations.decline', $reservation) }}" data-loading>@csrf @method('PATCH')
                                            <x-button type="submit" size="sm" variant="secondary">Decline</x-button>
                                        </form>
                                        @break
                                    @case('accepted')
                                        @if (! $listing->medicine->requires_prescription || $reservation->request?->status->value === 'reserved')
                                            <form method="POST" action="{{ route('reservations.complete', $reservation) }}" data-loading>@csrf @method('PATCH')
                                                <x-button type="submit" size="sm" icon="check-circle">Mark handed over</x-button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" data-loading>@csrf @method('PATCH')
                                            <x-button type="submit" size="sm" variant="ghost">Release</x-button>
                                        </form>
                                        @break
                                    @default
                                        <x-badge>{{ $reservation->status->label() }}</x-badge>
                                @endswitch
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        @if ($locked)
            <x-card title="Details">
                <x-detail-list :columns="3" :items="[
                    'Brand name' => $listing->brand_name,
                    'Medicine' => $listing->medicine->label(),
                    'Quantity' => $listing->quantity.' '.$listing->unit,
                    'Expiry' => $listing->expiry_date->format('F Y').($months > 0 ? ' ('.$months.' '.Str::plural('month', $months).' left)' : ''),
                    'Batch number' => $listing->batch_number,
                    'Packaging' => $packaging,
                    'Area' => $listing->upazila->name.', '.$listing->district->name,
                    'Created' => $listing->created_at->format('j M Y'),
                    'Published' => $listing->published_at?->format('j M Y'),
                ]" />
                @if ($listing->images->isNotEmpty())
                    <ul class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($listing->images as $image)
                            <li><img src="{{ $image->url() }}" alt="Photo {{ $loop->iteration }} of {{ $listing->brand_name }}" class="aspect-square w-full rounded-control border border-line object-cover"></li>
                        @endforeach
                    </ul>
                @endif
                <x-slot:footer>
                    <p class="text-sm text-ink-muted">A listing that is {{ str_replace('_', ' ', $listing->status->value) }} cannot be edited, so its details stay the same for everyone involved.</p>
                </x-slot:footer>
            </x-card>
        @else
            @include('account.listings._form')
        @endif
    </div>

    @if (collect($actions)->contains(fn ($a) => $a->value === 'cancel'))
        <x-confirm-modal id="cancel-listing" title="Cancel this listing?" :action="route('listings.status', $listing)" method="PATCH"
            :fields="['action' => 'cancel']" confirm="Yes, cancel listing" tone="danger">
            People will no longer be able to find or request this medicine. This cannot be undone.
        </x-confirm-modal>
    @endif
    @if ($listing->status->isDeletable())
        <x-confirm-modal id="delete-listing" title="Delete this listing?" :action="route('listings.destroy', $listing)" method="DELETE" confirm="Yes, delete" tone="danger">
            The listing and its photos will be removed permanently.
        </x-confirm-modal>
    @endif
</x-layouts.app>
