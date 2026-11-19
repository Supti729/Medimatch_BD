<x-layouts.app title="My listings">
    <x-page-header title="My listings" description="Medicines you have offered. Every listing is reviewed before other people can see it.">
        <x-button :href="route('listings.create')" variant="accent" icon="plus">New listing</x-button>
    </x-page-header>

    <x-tabs class="mt-6" route="listings.index" :current="$tab" :counts="$counts"
        :tabs="['all' => 'All', 'active' => 'Active', 'review' => 'Drafts and review', 'closed' => 'Closed']" />

    @if ($listings->isEmpty())
        <x-card class="mt-6" :padded="false">
            <x-empty-state icon="gift" :title="$tab === 'all' ? 'You have not listed any medicine yet' : 'Nothing in this tab'"
                description="Sealed, unexpired medicines you no longer need can help someone nearby.">
                <x-button :href="route('listings.create')" variant="accent" icon="plus">New listing</x-button>
            </x-empty-state>
        </x-card>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($listings as $listing)
                @php $actions = \App\Enums\ListingAction::availableFor($listing->status); @endphp
                <li class="rounded-card border border-line bg-surface p-5 shadow-card">
                    <div class="flex flex-wrap items-start gap-4">
                        @if ($listing->images->first())
                            <img src="{{ $listing->images->first()->url() }}" alt="" class="size-12 shrink-0 rounded-control border border-line object-cover">
                        @else
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-700" aria-hidden="true"><x-icon name="squares-plus" class="size-6" /></span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold">
                                    <a href="{{ route('listings.edit', $listing) }}" class="hover:text-brand-700 hover:underline">{{ $listing->brand_name }} <span class="font-medium text-ink-soft">{{ $listing->medicine->strength }}</span></a>
                                </h2>
                                <x-status-badge :status="$listing->status" />
                                @if ($listing->medicine->requires_prescription)
                                    <x-badge tone="warning">Prescription check</x-badge>
                                @endif
                            </div>
                            <p class="mt-0.5 text-sm text-ink-muted">{{ $listing->medicine->generic_name }} · {{ $listing->medicine->dosage_form }} · {{ $listing->upazila->name }}, {{ $listing->district->name }}</p>

                            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                <div class="flex gap-1.5"><dt class="text-ink-muted">Quantity</dt><dd class="font-medium">{{ $listing->quantity }} {{ $listing->unit }}</dd></div>
                                <div class="flex gap-1.5"><dt class="text-ink-muted">Expires</dt><dd class="font-medium">{{ $listing->expiry_date->format('M Y') }}</dd></div>
                                <div class="flex gap-1.5"><dt class="text-ink-muted">Created</dt><dd class="font-medium">{{ $listing->created_at->format('j M Y') }}</dd></div>
                            </dl>
                        </div>

                        <div class="flex w-full items-center gap-2 sm:w-auto">
                            <x-button :href="route('listings.edit', $listing)" variant="secondary" size="sm" :icon="$listing->status->isEditable() ? 'pencil-square' : 'eye'" class="flex-1 sm:flex-none">
                                {{ $listing->status->isEditable() ? 'Edit' : 'View' }}
                            </x-button>

                            @if ($actions)
                                <x-dropdown>
                                    <x-slot:trigger>
                                        <x-button variant="secondary" size="sm" icon-right="chevron-down" data-dropdown-trigger aria-haspopup="true" aria-expanded="false">Actions</x-button>
                                    </x-slot:trigger>
                                    @foreach ($actions as $action)
                                        @if ($action->value === 'cancel')
                                            <x-dropdown.item data-modal-open="cancel-{{ $listing->id }}" icon="x-circle">{{ $action->label() }}</x-dropdown.item>
                                        @else
                                            <form method="POST" action="{{ route('listings.status', $listing) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="{{ $action->value }}">
                                                <x-dropdown.item type="submit">{{ $action->label() }}</x-dropdown.item>
                                            </form>
                                        @endif
                                    @endforeach
                                </x-dropdown>
                            @endif
                        </div>
                    </div>

                    @if ($listing->review_note)
                        <x-alert :tone="$listing->status->value === 'rejected' ? 'danger' : 'info'" title="Note from the moderator" class="mt-4">{{ $listing->review_note }}</x-alert>
                    @elseif ($listing->status->value === 'pending_review')
                        <p class="mt-4 flex items-center gap-2 text-sm text-ink-muted"><x-icon name="clock" class="size-4" /> Waiting for a moderator to review this listing before it becomes visible.</p>
                    @endif
                </li>

                @if (collect($actions)->contains(fn ($a) => $a->value === 'cancel'))
                    <x-confirm-modal :id="'cancel-'.$listing->id" title="Cancel this listing?" :action="route('listings.status', $listing)" method="PATCH"
                        :fields="['action' => 'cancel']" confirm="Yes, cancel listing" tone="danger">
                        People will no longer be able to find or request {{ $listing->brand_name }}. This cannot be undone.
                    </x-confirm-modal>
                @endif
            @endforeach
        </ul>

        <div class="mt-6">{{ $listings->links('partials.pagination') }}</div>
    @endif
</x-layouts.app>
