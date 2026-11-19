@php $labels = ['requested' => 'Requested', 'reserved' => 'Reserved', 'completed' => 'Completed', 'cancelled' => 'Cancelled']; @endphp

<x-layouts.admin title="Transactions" preview>
    <x-page-header title="Transaction monitoring" description="Every interaction between a donor and a recipient, from request to handover. No money changes hands on the platform." />

    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Totals">
        @foreach ($labels as $key => $label)
            <x-stat-card :label="$label" :value="$totals[$key] ?? 0" :href="route('admin.transactions.index', ['status' => $key])"
                :icon="['requested' => 'hand-raised', 'reserved' => 'clock', 'completed' => 'check-circle', 'cancelled' => 'x-circle'][$key]"
                :tone="['requested' => 'info', 'reserved' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'][$key]" />
        @endforeach
    </section>

    <x-card class="mt-6" :padded="false">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3 sm:px-6">
            <p class="text-sm text-ink-soft">{{ $status ? 'Showing '.strtolower($labels[$status]) : 'Showing all' }}</p>
            @if ($status) <a href="{{ route('admin.transactions.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Show all</a> @endif
        </div>

        @if ($transactions->isEmpty())
            <x-empty-state icon="arrows-right-left" title="No transactions with this status" />
        @else
            <x-table :headers="['Interaction', 'Medicine', 'Donor', 'Recipient', 'Started', 'Completed', 'Status']" caption="Transactions">
                @foreach ($transactions as $transaction)
                    <tr>
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">#{{ $transaction['id'] }}</th>
                        <td class="px-5 py-3.5">{{ $transaction['medicine'] }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><a href="{{ route('admin.users.show', $transaction['donor']['id']) }}" class="text-ink-soft hover:text-brand-700 hover:underline">{{ $transaction['donor']['name'] }}</a></td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><a href="{{ route('admin.users.show', $transaction['recipient']['id']) }}" class="text-ink-soft hover:text-brand-700 hover:underline">{{ $transaction['recipient']['name'] }}</a></td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $transaction['started_at']->format('j M Y') }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $transaction['completed_at']?->format('j M Y') ?? '—' }}</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$transaction['status']" /></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-layouts.admin>
