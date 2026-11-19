@php
    $statuses = ['active', 'matching', 'match_found', 'verification_pending', 'reserved', 'completed', 'cancelled', 'expired'];
    $options = collect($statuses)->mapWithKeys(fn ($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all();
@endphp

<x-layouts.admin title="Requests" preview>
    <x-page-header title="Request management" description="All medicine requests and where each one is in the matching process." />

    <x-card class="mt-6">
        <form method="GET" action="{{ route('admin.requests.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-64"><x-form.select name="status" label="Status" :selected="$status" placeholder="Any status" :options="$options" /></div>
            <x-button type="submit">Filter</x-button>
            @if ($status) <x-button :href="route('admin.requests.index')" variant="ghost">Clear</x-button> @endif
        </form>
    </x-card>

    <x-card class="mt-6" :padded="false">
        @if ($requests->isEmpty())
            <x-empty-state icon="clipboard-document-list" title="No requests with this status" />
        @else
            <x-table :headers="['Request', 'Medicine', 'Quantity', 'Requester', 'District', 'Prescription', 'Status', 'Created']" caption="Medicine requests">
                @foreach ($requests as $request)
                    <tr>
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">#{{ $request['id'] }}</th>
                        <td class="px-5 py-3.5">{{ $request['medicine'] }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $request['quantity'] }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><a href="{{ route('admin.users.show', $request['user']['id']) }}" class="text-ink-soft hover:text-brand-700 hover:underline">{{ $request['user']['name'] }}</a></td>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $request['district'] }}</td>
                        <td class="px-5 py-3.5">@if ($request['requires_prescription']) <x-badge tone="warning">Check needed</x-badge> @else <span class="text-ink-muted">—</span> @endif</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$request['status']" /></td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $request['created_at']->format('j M Y') }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-layouts.admin>
