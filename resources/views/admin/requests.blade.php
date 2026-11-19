<x-layouts.admin title="Requests">
    <x-page-header title="Request management" description="All medicine requests and where each one is in the process." />

    <x-card class="mt-6">
        <form method="GET" action="{{ route('admin.requests.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-64"><x-form.select name="status" label="Status" :selected="$status" placeholder="Any status"
                :options="collect($statuses)->mapWithKeys(fn ($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all()" /></div>
            <x-button type="submit">Filter</x-button>
            @if ($status) <x-button :href="route('admin.requests.index')" variant="ghost">Clear</x-button> @endif
        </form>
    </x-card>

    <x-card class="mt-6" :padded="false">
        @if ($requests->isEmpty())
            <x-empty-state icon="clipboard-document-list" title="No requests" />
        @else
            <x-table :headers="['Request', 'Medicine', 'Quantity', 'Requester', 'District', 'Prescription', 'Status', 'Created']" caption="Medicine requests">
                @foreach ($requests as $request)
                    <tr>
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">#{{ $request->id }}</th>
                        <td class="px-5 py-3.5">{{ $request->medicine->label() }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $request->quantity }} {{ $request->unit }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><a href="{{ route('admin.users.show', $request->owner) }}" class="text-ink-soft hover:text-brand-700 hover:underline">{{ $request->owner->name }}</a></td>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $request->district->name }}</td>
                        <td class="px-5 py-3.5">@if ($request->medicine->requires_prescription) <x-badge tone="warning">Check needed</x-badge> @else <span class="text-ink-muted">—</span> @endif</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$request->status" /></td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $request->created_at->format('j M Y') }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
    <div class="mt-6">{{ $requests->links('partials.pagination') }}</div>
</x-layouts.admin>
