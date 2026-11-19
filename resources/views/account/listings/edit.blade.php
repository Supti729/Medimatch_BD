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
