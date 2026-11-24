<x-layouts.admin title="Dashboard">
    <x-page-header title="Overview" description="Platform activity and the work waiting for moderators." />

    {{-- Numbers --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Platform numbers">
        <x-stat-card label="Total users" :value="$stats['total_users']" :hint="$stats['active_users'].' active'" icon="users" :href="route('admin.users.index')" />
        <x-stat-card label="Total listings" :value="$stats['total_listings']" :hint="$stats['active_listings'].' visible to the public'" icon="gift" tone="accent" :href="route('admin.listings.index')" />
        <x-stat-card label="Total requests" :value="$stats['total_requests']" icon="clipboard-document-list" tone="info" :href="route('admin.requests.index')" />
        <x-stat-card label="Pending verification" :value="$stats['pending_verification']" hint="Documents waiting for a reviewer" icon="document-check" tone="warning" :href="route('admin.verifications.index')" />
        <x-stat-card label="Open reports" :value="$stats['open_reports']" hint="Including escalated" icon="flag" tone="danger" :href="route('admin.reports.index')" />
        <x-stat-card label="Completed handovers" :value="$stats['completed']" icon="check-circle" tone="success" :href="route('admin.transactions.index', ['status' => 'completed'])" />
    </section>
    <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-ink-muted">
        <p>Partner accounts: <a href="{{ route('admin.partners.index') }}" class="font-semibold text-brand-700 hover:underline">{{ $stats['partners'] }}</a></p>
        <p class="flex items-center gap-2">
            AI matching service:
            @switch($matchingHealth['status'])
                @case('online')
                    <x-badge tone="success" dot>Online</x-badge>
                    <span class="text-xs">model {{ $matchingHealth['semantic_model'] ?? '?' }}{{ ($matchingHealth['model_loaded'] ?? false) ? '' : ', loading' }}</span>
                    @break
                @case('disabled')
                    <x-badge dot>Switched off</x-badge> <span class="text-xs">searches use keywords</span>
                    @break
                @default
                    <x-badge tone="warning" dot>Not reachable</x-badge> <span class="text-xs">searches fall back to keywords; run <code>php artisan ai:status</code></span>
            @endswitch
        </p>
    </div>

    {{-- Work queues --}}
    <section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3" aria-label="Waiting for action">
        <x-card title="Listings to review" :padded="false">
            <x-slot:actions><x-button :href="route('admin.listings.index', ['tab' => 'pending'])" size="sm" variant="secondary">All</x-button></x-slot:actions>
            @forelse ($pendingListings as $listing)
                <a href="{{ route('admin.listings.show', $listing) }}" class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-brand-50/60 sm:px-6">
                    <span class="min-w-0"><span class="block truncate font-medium">{{ $listing->brand_name }} {{ $listing->medicine->strength }}</span><span class="text-xs text-ink-muted">{{ $listing->owner->name }} · {{ $listing->district->name }}</span></span>
                    <x-icon name="chevron-right" class="size-4 text-ink-muted" />
                </a>
            @empty
                <p class="p-5 text-sm text-ink-muted sm:p-6">No listings are waiting for review.</p>
            @endforelse
        </x-card>

        <x-card title="Documents to review" :padded="false">
            <x-slot:actions><x-button :href="route('admin.verifications.index')" size="sm" variant="secondary">All</x-button></x-slot:actions>
            @forelse ($pendingVerifications as $submission)
                <a href="{{ route('admin.verifications.show', $submission) }}" class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-brand-50/60 sm:px-6">
                    <span class="min-w-0"><span class="block truncate font-medium">{{ $submission->request->medicine->label() }}</span><span class="text-xs text-ink-muted">Submitted {{ $submission->created_at->diffForHumans() }}</span></span>
                    <x-status-badge :status="$submission->status" />
                </a>
            @empty
                <p class="p-5 text-sm text-ink-muted sm:p-6">No documents are waiting for review.</p>
            @endforelse
        </x-card>

        <x-card title="Open reports" :padded="false">
            <x-slot:actions><x-button :href="route('admin.reports.index')" size="sm" variant="secondary">All</x-button></x-slot:actions>
            @forelse ($openReports as $report)
                <a href="{{ route('admin.reports.show', $report) }}" class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-brand-50/60 sm:px-6">
                    <span class="min-w-0"><span class="block truncate font-medium">{{ $report->reasonLabel() }}</span><span class="block truncate text-xs text-ink-muted">About a {{ $report->target_type }} · {{ $report->created_at->diffForHumans() }}</span></span>
                    <x-status-badge :status="$report->status" />
                </a>
            @empty
                <p class="p-5 text-sm text-ink-muted sm:p-6">There are no open reports.</p>
            @endforelse
        </x-card>
    </section>

    {{-- Charts --}}
    <section class="mt-8" aria-labelledby="charts-heading">
        <h2 id="charts-heading" class="text-xl">Trends</h2>
        <p class="mt-1 text-sm text-ink-muted">Counted live from the database. Hover or focus a bar for its value.</p>

        <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-card title="New users per month" description="Accounts created in the last six months">
                <x-chart.columns :data="$charts['registrations']" colour="bg-chart-1" unit="users" />
            </x-card>

            <x-card title="Completed handovers per month" description="Handovers marked as completed">
                <x-chart.columns :data="$charts['completed']" colour="bg-chart-1" unit="handovers" />
            </x-card>

            <x-card title="Listings by district" description="Top districts, all statuses">
                <x-chart.bars :data="$charts['listings_by_district']" colour="bg-chart-1" unit="listings" label-heading="District" />
            </x-card>

            <x-card title="Requests by district" description="Top districts, all statuses">
                <x-chart.bars :data="$charts['requests_by_district']" colour="bg-chart-2" unit="requests" label-heading="District" />
            </x-card>

            <x-card title="Donation and affordable-access listings" description="Share of all listings by type">
                <x-chart.split :data="$charts['listing_types']" :colours="['bg-chart-3', 'bg-chart-4']" unit="listings" />
            </x-card>

            <x-card title="Latest admin actions" :padded="false">
                <x-slot:actions><x-button :href="route('admin.audit-logs.index')" size="sm" variant="secondary">Audit log</x-button></x-slot:actions>
                @if ($recentAudit->isEmpty())
                    <p class="p-5 text-sm text-ink-muted sm:p-6">No actions recorded yet.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($recentAudit as $entry)
                            <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm sm:px-6">
                                <span class="min-w-0"><code class="rounded bg-canvas px-1.5 py-0.5 text-xs">{{ $entry->action }}</code> <span class="mt-0.5 block truncate text-xs text-ink-muted">{{ $entry->description }} · {{ $entry->actor?->name ?? 'System' }}</span></span>
                                <span class="shrink-0 text-xs text-ink-muted">{{ $entry->created_at->diffForHumans(short: true) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </section>
</x-layouts.admin>
