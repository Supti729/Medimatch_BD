@php
    $expiry = \Illuminate\Support\Carbon::parse($listing['expiry'].'-01');
    $monthsLeft = (int) floor(now()->startOfMonth()->diffInMonths($expiry));
    $shelfLifeOk = $monthsLeft >= $settings['min_shelf_life_months'];

    // Automatic checks a moderator sees first. The moderator still makes the decision.
    $checks = [
        ['Shelf life of at least '.$settings['min_shelf_life_months'].' months', $shelfLifeOk, $monthsLeft.' '.Str::plural('month', $monthsLeft).' left'],
        ['Quantity within the limit of '.$settings['max_quantity'], $listing['quantity'] <= $settings['max_quantity'], $listing['quantity'].' '.$listing['unit']],
        ['Donation listing (individuals may not sell)', $listing['type'] === 'donation' || $settings['allow_affordable_access'], config('medimatch.listing_types')[$listing['type']]],
        ['No open reports', $reports->whereIn('status', ['open', 'escalated'])->isEmpty(), $reports->count().' '.Str::plural('report', $reports->count())],
    ];

    $options = match ($listing['status']) {
        'pending_review' => ['approve' => ['Approve', 'The listing becomes visible to the public.'], 'reject' => ['Reject', 'The owner is told the reason and can correct it.']],
        'blocked' => ['unblock' => ['Unblock', 'The listing becomes visible again.']],
        'rejected', 'completed', 'cancelled' => [],
        default => ['block' => ['Block', 'Hide an unsafe or misleading listing immediately.']],
    };
@endphp

<x-layouts.admin :title="'Listing #'.$listing['id']" preview>
    <a href="{{ route('admin.listings.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" /> Listings</a>

    <x-page-header class="mt-3" :title="$listing['name'].' '.$listing['strength']" :description="'Listing #'.$listing['id'].' · '.$listing['generic_name'].' · '.$listing['dosage_form']">
        <x-status-badge :status="$listing['status']" />
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card title="Listing details">
                <x-detail-list :columns="3" :items="[
                    'Medicine' => $listing['name'],
                    'Generic name' => $listing['generic_name'],
                    'Strength' => $listing['strength'],
                    'Dosage form' => $listing['dosage_form'],
                    'Quantity' => $listing['quantity'].' '.$listing['unit'],
                    'Expiry' => $expiry->format('F Y'),
                    'Packaging' => $listing['packaging'],
                    'Manufacturer' => $listing['manufacturer'],
                    'Type' => config('medimatch.listing_types')[$listing['type']],
                    'Area' => $listing['area'].', '.$listing['district'],
                    'Prescription medicine' => $listing['requires_prescription'] ? 'Yes' : 'No',
                    'Listed' => $listing['posted_at']->format('j M Y'),
                ]" />
            </x-card>

            <x-card title="Photos">
                <x-empty-state icon="photo" title="No photos in the sample data" class="py-6"
                    description="Uploaded pack photos will be shown here so the moderator can check the name, strength, seal and expiry date." />
            </x-card>

            <x-card title="Automatic checks" description="These help the moderator. They do not approve or reject a listing by themselves.">
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
                            <li><a href="{{ route('admin.reports.show', $report['id']) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm hover:bg-brand-50/60 sm:px-6">
                                <span class="min-w-0"><span class="block font-medium">{{ $report['reason'] }}</span><span class="block truncate text-xs text-ink-muted">{{ $report['details'] }}</span></span>
                                <x-status-badge :status="$report['status']" />
                            </a></li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Owner">
                <p class="text-sm font-semibold"><a href="{{ route('admin.users.show', $listing['owner']['id']) }}" class="hover:text-brand-700 hover:underline">{{ $listing['owner']['name'] }}</a></p>
                <p class="mt-1 text-sm text-ink-muted">User #{{ $listing['owner']['id'] }}</p>
            </x-card>

            @if ($options)
                <x-admin.decision title="Moderation decision" route="admin.listings.decide" :param="$listing['id']" :options="$options" feature="Moderating a listing" />
            @else
                <x-card title="Moderation decision">
                    <p class="text-sm text-ink-soft">A listing that is {{ str_replace('_', ' ', $listing['status']) }} has no further moderation actions.</p>
                </x-card>
            @endif

            <x-admin.audit-list :entries="$audit" />
        </aside>
    </div>
</x-layouts.admin>
