<x-layouts.admin title="Listings">
    <x-page-header title="Listing management" description="Review new listings and moderate existing ones. Blocked and rejected listings are never shown to the public." />

    <x-tabs class="mt-6" route="admin.listings.index" :current="$tab" :counts="$counts"
        :tabs="['all' => 'All', 'pending' => 'Waiting for review', 'public' => 'Public', 'blocked' => 'Blocked and rejected']" />

    <x-card class="mt-6" :padded="false">
        @if ($listings->isEmpty())
            <x-empty-state icon="gift" title="Nothing in this tab" description="Listings will appear here when they reach this state." />
        @else
            <x-table :headers="['Medicine', 'Owner', 'District', 'Expires', 'Status', 'Reports', '']" caption="Medicine listings">
                @foreach ($listings as $listing)
                    <tr class="hover:bg-brand-50/40">
                        <th scope="row" class="px-5 py-3.5 font-medium">
                            <a href="{{ route('admin.listings.show', $listing) }}" class="hover:text-brand-700 hover:underline">{{ $listing->brand_name }} {{ $listing->medicine->strength }}</a>
                            <span class="block text-xs font-normal text-ink-muted">{{ $listing->medicine->generic_name }} · {{ $listing->medicine->dosage_form }}</span>
                        </th>
                        <td class="px-5 py-3.5 whitespace-nowrap"><a href="{{ route('admin.users.show', $listing->owner) }}" class="text-ink-soft hover:text-brand-700 hover:underline">{{ $listing->owner->name }}</a></td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $listing->district->name }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $listing->expiry_date->format('M Y') }}</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$listing->status" /></td>
                        <td class="px-5 py-3.5 tabular-nums">@if ($reportCounts[$listing->id] ?? 0) <span class="font-semibold text-danger-700">{{ $reportCounts[$listing->id] }}</span> @else 0 @endif</td>
                        <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.listings.show', $listing)" size="sm" variant="secondary">Review</x-button></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
    <div class="mt-6">{{ $listings->links('partials.pagination') }}</div>
</x-layouts.admin>
