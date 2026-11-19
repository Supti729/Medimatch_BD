@php
    $labels = ['pending' => 'Waiting for donor', 'accepted' => 'Reserved', 'completed' => 'Completed', 'declined' => 'Declined', 'cancelled' => 'Cancelled'];
    $icons = ['pending' => ['hand-raised', 'info'], 'accepted' => ['clock', 'warning'], 'completed' => ['check-circle', 'success'], 'declined' => ['x-circle', 'danger'], 'cancelled' => ['x-mark', 'danger']];
@endphp

<x-layouts.admin title="Transactions">
    <x-page-header title="Transaction monitoring" description="Every request for a listing, from asking to handover. No money changes hands on the platform." />

    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5" aria-label="Totals">
        @foreach ($labels as $key => $label)
            <x-stat-card :label="$label" :value="(int) ($totals[$key] ?? 0)" :href="route('admin.transactions.index', ['status' => $key])" :icon="$icons[$key][0]" :tone="$icons[$key][1]" />
        @endforeach
    </section>

    <x-card class="mt-6" :padded="false">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3 sm:px-6">
            <p class="text-sm text-ink-soft">{{ $status ? 'Showing: '.mb_strtolower($labels[$status]) : 'Showing all' }}</p>
            @if ($status) <a href="{{ route('admin.transactions.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Show all</a> @endif
        </div>

        @if ($transactions->isEmpty())
            <x-empty-state icon="arrows-right-left" title="No transactions" />
        @else
            <x-table :headers="['#', 'Medicine', 'Donor', 'Requester', 'Started', 'Completed', 'Status']" caption="Transactions">
                @foreach ($transactions as $t)
                    <tr>
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">{{ $t->id }}</th>
                        <td class="px-5 py-3.5"><a href="{{ route('admin.listings.show', $t->medicine_listing_id) }}" class="hover:text-brand-700 hover:underline">{{ $t->listing->brand_name }} {{ $t->listing->medicine->strength }}</a></td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><a href="{{ route('admin.users.show', $t->listing->owner) }}" class="text-ink-soft hover:text-brand-700 hover:underline">{{ $t->listing->owner->name }}</a></td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><a href="{{ route('admin.users.show', $t->requester) }}" class="text-ink-soft hover:text-brand-700 hover:underline">{{ $t->requester->name }}</a></td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $t->created_at->format('j M Y') }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $t->completed_at?->format('j M Y') ?? '—' }}</td>
                        <td class="px-5 py-3.5"><x-badge :tone="['pending' => 'info', 'accepted' => 'warning', 'completed' => 'success', 'declined' => 'neutral', 'cancelled' => 'neutral'][$t->status->value]">{{ $t->status->label() }}</x-badge></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
    <div class="mt-6">{{ $transactions->links('partials.pagination') }}</div>
</x-layouts.admin>
