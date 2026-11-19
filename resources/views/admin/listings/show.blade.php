@php
    $rules = config('medimatch.listing_rules');
    $months = $listing->monthsUntilExpiry();
    $openReports = $reports->filter(fn ($r) => $r->status->isOpen());

    // Automatic checks help the moderator. They never decide on their own.
    $checks = [
        ['Shelf life of at least '.$rules['min_shelf_life_months'].' months', $months >= $rules['min_shelf_life_months'], $months.' '.Str::plural('month', $months).' left'],
        ['Quantity within the limit of '.$rules['max_quantity'], $listing->quantity <= $rules['max_quantity'], $listing->quantity.' '.$listing->unit],
        ['Medicine may be listed', $listing->medicine->isListable(), $listing->medicine->isListable() ? 'In the catalogue' : $listing->medicine->restriction_reason],
        ['Brand name is a known alias', $listing->medicine_alias_id !== null, $listing->brand_name],
        ['Has photos of the pack', $listing->images->isNotEmpty(), $listing->images->count().' '.Str::plural('photo', $listing->images->count())],
        ['No open reports', $openReports->isEmpty(), $reports->count().' '.Str::plural('report', $reports->count())],
    ];

    $options = match ($listing->status->value) {
        'pending_review' => ['approve' => ['Approve', 'The listing becomes visible, and people with a matching request nearby are notified.'], 'reject' => ['Reject', 'The owner sees your reason and can correct it.']],
        'blocked' => ['unblock' => ['Unblock', 'The listing becomes visible again.']],
        'available', 'requested', 'reserved', 'paused', 'unavailable' => ['block' => ['Block', 'Hide an unsafe or misleading listing at once. Anyone waiting for it is released.']],
        default => [],
    };
@endphp

<x-layouts.admin :title="'Listing #'.$listing->id">
    <a href="{{ route('admin.listings.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" /> Listings</a>

    <x-page-header class="mt-3" :title="$listing->brand_name.' '.$listing->medicine->strength" :description="'Listing #'.$listing->id.' · '.$listing->medicine->generic_name.' · '.$listing->medicine->dosage_form">
        <x-status-badge :status="$listing->status" />
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card title="Listing details">
                <x-detail-list :columns="3" :items="[
                    'Brand name' => $listing->brand_name,
                    'Medicine' => $listing->medicine->label(),
                    'Quantity' => $listing->quantity.' '.$listing->unit,
                    'Expiry' => $listing->expiry_date->format('F Y'),
                    'Batch number' => $listing->batch_number,
                    'Packaging' => config('medimatch.packaging')[$listing->packaging] ?? $listing->packaging,
                    'Manufacturer' => $listing->alias?->manufacturer,
                    'Type' => config('medimatch.listing_types')[$listing->type] ?? $listing->type,
                    'Area' => $listing->upazila->name.', '.$listing->district->name,
                    'Prescription medicine' => $listing->medicine->requires_prescription ? 'Yes' : 'No',
                    'Submitted' => $listing->submitted_at?->format('j M Y, g:i a'),
                    'Published' => $listing->published_at?->format('j M Y'),
                ]" />
                @if ($listing->review_note)
                    <x-alert tone="info" title="Last moderator note" class="mt-5">{{ $listing->review_note }}</x-alert>
                @endif
            </x-card>

            <x-card title="Photos">
                @if ($listing->images->isEmpty())
                    <p class="text-sm text-ink-muted">The owner did not add photos. Consider asking for them before approving.</p>
                @else
                    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($listing->images as $image)
                            <li><a href="{{ $image->url() }}" target="_blank"><img src="{{ $image->url() }}" alt="Photo {{ $loop->iteration }}" class="aspect-square w-full rounded-control border border-line object-cover"></a></li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="Automatic checks" description="These help you decide. They do not approve or reject a listing by themselves.">
                <ul class="space-y-3 text-sm">
                    @foreach ($checks as [$label, $passed, $detail])
                        <li class="flex items-start gap-3">
                            <x-icon :name="$passed ? 'check-circle' : 'exclamation-triangle'" @class(['mt-0.5 size-5', 'text-success-600' => $passed, 'text-warning-600' => ! $passed]) />
                            <span class="flex-1"><span class="font-medium">{{ $label }}</span> <span class="text-ink-muted">· {{ $detail }}</span></span>
                            <span class="text-xs font-semibold {{ $passed ? 'text-success-700' : 'text-warning-700' }}">{{ $passed ? 'Passed' : 'Check' }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card title="Reports on this listing" :padded="false">
                @if ($reports->isEmpty())
                    <p class="p-5 text-sm text-ink-muted sm:p-6">Nobody has reported this listing.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($reports as $report)
                            <li><a href="{{ route('admin.reports.show', $report) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm hover:bg-brand-50/60 sm:px-6">
                                <span class="min-w-0"><span class="block font-medium">{{ $report->reasonLabel() }}</span><span class="block truncate text-xs text-ink-muted">{{ $report->details ?? 'No details given' }}</span></span>
                                <x-status-badge :status="$report->status" />
                            </a></li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Owner">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$listing->owner" size="sm" />
                    <div><a href="{{ route('admin.users.show', $listing->owner) }}" class="text-sm font-semibold hover:text-brand-700 hover:underline">{{ $listing->owner->name }}</a>
                        <p class="text-xs text-ink-muted">User #{{ $listing->owner->id }} · <x-status-badge :status="$listing->owner->status" /></p></div>
                </div>
            </x-card>

            @if ($options)
                <x-admin.decision title="Moderation decision" route="admin.listings.decide" :param="$listing->id" :options="$options" feature="Moderating a listing" />
            @else
                <x-card title="Moderation decision">
                    <p class="text-sm text-ink-soft">A listing that is {{ str_replace('_', ' ', $listing->status->value) }} has no moderation actions.</p>
                </x-card>
            @endif

            <x-admin.audit-list :entries="$audit" />
        </aside>
    </div>
</x-layouts.admin>
