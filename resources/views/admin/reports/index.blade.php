<x-layouts.admin title="Reports">
    <x-page-header title="Reports" description="Reports from users about listings and conversations." />

    <x-tabs class="mt-6" route="admin.reports.index" :current="$tab" :counts="$counts" :tabs="['open' => 'Open', 'closed' => 'Closed', 'all' => 'All']" />

    <x-card class="mt-6" :padded="false">
        @if ($reports->isEmpty())
            <x-empty-state icon="flag" title="No reports here" description="Reports appear here when users send them." />
        @else
            <x-table :headers="['Report', 'Reason', 'About', 'Reported user', 'From', 'Received', 'Status', '']" caption="User reports">
                @foreach ($reports as $report)
                    <tr class="hover:bg-brand-50/40">
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">#{{ $report->id }}</th>
                        <td class="px-5 py-3.5 font-medium">{{ $report->reasonLabel() }}</td>
                        <td class="px-5 py-3.5"><x-badge>{{ ucfirst($report->target_type) }}</x-badge></td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $report->reportedUser?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $report->reporter->name }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $report->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$report->status" /></td>
                        <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.reports.show', $report)" size="sm" variant="secondary">Open</x-button></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
    <div class="mt-6">{{ $reports->links('partials.pagination') }}</div>
</x-layouts.admin>
