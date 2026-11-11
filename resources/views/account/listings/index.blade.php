@php
    // What the owner may do in each status. The backend enforces the same rules (push B2).
    $actions = [
        'draft' => ['Submit for review', 'Delete draft'],
        'pending_review' => ['Withdraw from review'],
        'available' => ['Pause listing', 'Mark as unavailable', 'Cancel listing'],
        'requested' => ['Pause listing', 'Cancel listing'],
        'paused' => ['Reactivate listing', 'Cancel listing'],
        'reserved' => ['Mark as completed', 'Release reservation'],
        'rejected' => ['Delete listing'],
    ];
    $editable = ['draft', 'available', 'paused', 'rejected'];
    $connected = Route::has('listings.update');
@endphp

<x-layouts.app title="My listings" :preview="$usingSampleData">
    <x-page-header title="My listings" description="Medicines you have offered. You can edit a listing until someone has reserved it.">
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
                @php $expiry = \Illuminate\Support\Carbon::parse($listing['expiry'].'-01'); @endphp
                <li class="rounded-card border border-line bg-surface p-5 shadow-card">
                    <div class="flex flex-wrap items-start gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-700" aria-hidden="true"><x-icon name="squares-plus" class="size-6" /></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold">{{ $listing['name'] }} <span class="font-medium text-ink-soft">{{ $listing['strength'] }}</span></h2>
                                <x-status-badge :status="$listing['status']" />
                            </div>
                            <p class="mt-0.5 text-sm text-ink-muted">{{ $listing['generic_name'] }} · {{ $listing['dosage_form'] }}</p>

                            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                <div class="flex gap-1.5"><dt class="text-ink-muted">Quantity</dt><dd class="font-medium">{{ $listing['quantity'] }} {{ $listing['unit'] }}</dd></div>
                                <div class="flex gap-1.5"><dt class="text-ink-muted">Expires</dt><dd class="font-medium">{{ $expiry->format('M Y') }}</dd></div>
                                <div class="flex gap-1.5"><dt class="text-ink-muted">Created</dt><dd class="font-medium">{{ $listing['posted_at']->format('j M Y') }}</dd></div>
                                @if ($listing['requests_count'])
                                    <div class="flex gap-1.5"><dt class="text-ink-muted">Requests</dt><dd class="font-medium">{{ $listing['requests_count'] }}</dd></div>
                                @endif
                            </dl>
                        </div>

                        <div class="flex w-full items-center gap-2 sm:w-auto">
                            @if (in_array($listing['status'], $editable, true))
                                <x-button :href="route('listings.edit', $listing['id'])" variant="secondary" size="sm" icon="pencil-square" class="flex-1 sm:flex-none">Edit</x-button>
                            @else
                                <x-button :href="route('listings.edit', $listing['id'])" variant="secondary" size="sm" icon="eye" class="flex-1 sm:flex-none">View</x-button>
                            @endif

                            @isset($actions[$listing['status']])
                                <x-dropdown>
                                    <x-slot:trigger>
                                        <x-button variant="secondary" size="sm" icon-right="chevron-down" data-dropdown-trigger aria-haspopup="true" aria-expanded="false">Actions</x-button>
                                    </x-slot:trigger>
                                    @foreach ($actions[$listing['status']] as $action)
                                        <x-dropdown.item :disabled="! $connected">{{ $action }}</x-dropdown.item>
                                    @endforeach
                                    @unless ($connected)
                                        <p class="border-t border-line px-4 pt-2 pb-1 text-xs text-ink-muted">Not connected yet. These actions work once the backend is added.</p>
                                    @endunless
                                </x-dropdown>
                            @endisset
                        </div>
                    </div>

                    @if ($listing['review_note'])
                        <x-alert tone="danger" title="Reviewer note" class="mt-4">{{ $listing['review_note'] }}</x-alert>
                    @elseif ($listing['status'] === 'pending_review')
                        <p class="mt-4 flex items-center gap-2 text-sm text-ink-muted"><x-icon name="clock" class="size-4" /> Waiting for a moderator to review this listing before it becomes visible.</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
