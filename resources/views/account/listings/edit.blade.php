@php
    $locked = ! in_array($listing['status'], ['draft', 'available', 'paused', 'rejected'], true);
@endphp

<x-layouts.app :title="($locked ? 'Listing: ' : 'Edit listing: ').$listing['name']" :preview="$usingSampleData">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('listings.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700">
            <x-icon name="arrow-left" class="size-4" /> My listings
        </a>

        <x-page-header class="mt-3" :title="$listing['name'].' '.$listing['strength']" :description="$locked ? null : 'Changes to an approved listing are reviewed again before they appear.'">
            <x-status-badge :status="$listing['status']" />
        </x-page-header>

        <div class="my-6 space-y-4">
            @if ($listing['review_note'])
                <x-alert tone="danger" title="Reviewer note">{{ $listing['review_note'] }} Correct the listing and submit it again.</x-alert>
            @endif
            @if ($locked)
                <x-alert tone="warning" title="This listing cannot be edited">
                    A listing that is {{ str_replace('_', ' ', $listing['status']) }} is locked so its details stay the same for the people involved.
                </x-alert>
            @endif
        </div>

        @include('account.listings._form', [
            'listing' => $listing,
            'location' => ['division' => $listing['division'], 'district' => $listing['district'], 'upazila' => $listing['area']],
            'storeRoute' => 'listings.update',
            'locked' => $locked,
        ])
    </div>
</x-layouts.app>
