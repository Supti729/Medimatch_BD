<x-layouts.admin :title="'Report #'.$report->id">
    <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" /> Reports</a>

    <x-page-header class="mt-3" :title="$report->reasonLabel()" :description="'Report #'.$report->id.' · received '.$report->created_at->format('j M Y, g:i a')">
        <x-status-badge :status="$report->status" />
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card title="What was reported">
                <x-detail-list :items="['About' => ucfirst($report->target_type).' #'.$report->target_id, 'Reported by' => $report->reporter->name, 'Reason' => $report->reasonLabel()]" />
                <div class="mt-5 rounded-control border border-line bg-canvas p-4">
                    <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Reporter's description</p>
                    <p class="mt-1.5 text-sm whitespace-pre-line">{{ $report->details ?: 'No details given.' }}</p>
                </div>
            </x-card>

            <x-card title="Evidence">
                @if (! $target)
                    <p class="text-sm text-ink-muted">The reported {{ $report->target_type }} no longer exists.</p>
                @elseif ($report->target_type === 'listing')
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">{{ $target->brand_name }} {{ $target->medicine->strength }}</p>
                            <p class="text-sm text-ink-muted">{{ $target->medicine->label() }} · {{ $target->quantity }} {{ $target->unit }} · expires {{ $target->expiry_date->format('M Y') }}</p>
                        </div>
                        <x-status-badge :status="$target->status" />
                    </div>
                    <x-button :href="route('admin.listings.show', $target)" variant="secondary" size="sm" class="mt-4">Open listing review (block or unblock)</x-button>
                @else
                    <x-alert tone="warning" class="mb-4">You are reading a private conversation because it was reported. This view is recorded in the audit log.</x-alert>
                    <ol class="max-h-96 space-y-2 overflow-y-auto rounded-control bg-canvas p-4 text-sm">
                        @forelse ($messages as $message)
                            <li><span class="font-semibold">{{ $message->sender->name }}</span> <span class="text-xs text-ink-muted">{{ $message->created_at->format('j M, g:i a') }}</span>
                                <p class="whitespace-pre-line text-ink-soft">{{ $message->body }}</p></li>
                        @empty
                            <li class="text-ink-muted">No messages.</li>
                        @endforelse
                    </ol>
                @endif
            </x-card>

            <x-card title="Other reports about {{ $report->reportedUser?->name ?? 'this user' }}" :padded="false">
                @if ($related->isEmpty())
                    <p class="p-5 text-sm text-ink-muted sm:p-6">There are no other reports about this user.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($related as $other)
                            <li><a href="{{ route('admin.reports.show', $other) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm hover:bg-brand-50/60 sm:px-6">
                                <span class="min-w-0"><span class="block font-medium">{{ $other->reasonLabel() }}</span><span class="block truncate text-xs text-ink-muted">About a {{ $other->target_type }} · {{ $other->created_at->diffForHumans() }}</span></span>
                                <x-status-badge :status="$other->status" />
                            </a></li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            @if ($report->reportedUser)
                <x-card title="Reported user">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('admin.users.show', $report->reportedUser) }}" class="text-sm font-semibold hover:text-brand-700 hover:underline">{{ $report->reportedUser->name }}</a>
                        <x-status-badge :status="$report->reportedUser->status" />
                    </div>
                    <p class="mt-2 text-sm text-ink-muted">Suspending an account is done on the user's page, with its own reason.</p>
                </x-card>
            @endif

            @if ($report->status->isOpen())
                <x-admin.decision title="Resolve report" route="admin.reports.decide" :param="$report->id" feature="Resolving a report"
                    note-hint="Recorded in the audit log. The reporter is told the outcome, not the details."
                    :options="[
                        'resolved' => ['Resolve', 'Action was taken, for example the listing was blocked or the user suspended.'],
                        'rejected' => ['Reject', 'The report is not valid. No action is needed.'],
                        'escalated' => ['Escalate', 'Needs a senior administrator or an outside authority. Stays open.'],
                    ]" />
            @else
                <x-card title="Decision">
                    <p class="text-sm text-ink-soft">{{ ucfirst($report->status->value) }} by {{ $report->handler?->name ?? 'a moderator' }} on {{ $report->resolved_at?->format('j M Y') }}.</p>
                    @if ($report->resolution_note) <p class="mt-2 text-sm text-ink-soft">“{{ $report->resolution_note }}”</p> @endif
                </x-card>
            @endif

            <x-admin.audit-list :entries="$audit" title="Actions on this report" />
        </aside>
    </div>
</x-layouts.admin>
