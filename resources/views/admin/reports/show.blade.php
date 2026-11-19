@php $closed = in_array($report['status'], ['resolved', 'rejected'], true); @endphp

<x-layouts.admin :title="'Report #'.$report['id']" preview>
    <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" /> Reports</a>

    <x-page-header class="mt-3" :title="$report['reason']" :description="'Report #'.$report['id'].' · received '.$report['created_at']->format('j M Y, g:i a')">
        <x-status-badge :status="$report['status']" />
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card title="What was reported">
                <x-detail-list :items="[
                    'About' => $report['target'],
                    'Type' => ucfirst($report['target_type']),
                    'Reported by' => $report['reporter']['name'],
                    'Reason' => $report['reason'],
                ]" />
                <div class="mt-5 rounded-control border border-line bg-canvas p-4">
                    <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Reporter's description</p>
                    <p class="mt-1.5 text-sm">{{ $report['details'] }}</p>
                </div>
            </x-card>

            <x-card title="Evidence">
                <x-empty-state icon="paper-clip" title="No evidence in the sample data" class="py-6"
                    description="In the backend part this shows the reported listing or the reported message with its surrounding messages, so the moderator can judge the context." />
            </x-card>

            <x-card title="Other reports about {{ $report['reported_user']['name'] }}" :padded="false">
                @if ($related->isEmpty())
                    <p class="p-5 text-sm text-ink-muted sm:p-6">There are no other reports about this user.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($related as $other)
                            <li><a href="{{ route('admin.reports.show', $other['id']) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm hover:bg-brand-50/60 sm:px-6">
                                <span class="min-w-0"><span class="block font-medium">{{ $other['reason'] }}</span><span class="block truncate text-xs text-ink-muted">{{ $other['target'] }}</span></span>
                                <x-status-badge :status="$other['status']" />
                            </a></li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Reported user">
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('admin.users.show', $report['reported_user']['id']) }}" class="text-sm font-semibold hover:text-brand-700 hover:underline">{{ $report['reported_user']['name'] }}</a>
                    <x-status-badge :status="$report['reported_user']['status']" />
                </div>
                <p class="mt-2 text-sm text-ink-muted">Suspending an account is done from the user's page, with its own reason.</p>
            </x-card>

            @if ($closed)
                <x-card title="Decision">
                    <p class="text-sm text-ink-soft">This report is {{ $report['status'] }}. Reopening is not available in the prototype.</p>
                </x-card>
            @else
                <x-admin.decision title="Resolve report" route="admin.reports.decide" :param="$report['id']" feature="Resolving a report"
                    note-hint="Recorded in the audit log. The reporter is told the outcome, not the details."
                    :options="[
                        'resolved' => ['Resolve', 'Action was taken, for example the listing was blocked.'],
                        'rejected' => ['Reject', 'The report is not valid. No action is needed.'],
                        'escalated' => ['Escalate', 'Needs a senior administrator or an outside authority.'],
                    ]" />
            @endif

            <x-admin.audit-list :entries="$audit" title="Actions on this report" />
        </aside>
    </div>
</x-layouts.admin>
