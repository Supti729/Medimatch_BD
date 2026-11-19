<x-layouts.admin title="Listings" preview>
    <x-page-header title="Listing management" description="Review new listings and moderate existing ones. Blocked and rejected listings are never shown to the public." />

    <x-tabs class="mt-6" route="admin.listings.index" :current="$tab" :counts="$counts"
        :tabs="['all' => 'All', 'pending' => 'Waiting for review', 'public' => 'Public', 'blocked' => 'Blocked and rejected']" />

    <x-card class="mt-6" :padded="false">
        @if ($listings->isEmpty())
            <x-empty-state icon="gift" title="Nothing in this tab" description="Listings will appear here when they reach this state." />
        @else
            <x-table :headers="['Medicine', 'Owner', 'Area', 'Type', 'Expires', 'Status', 'Reports', '']" caption="Medicine listings">
                @foreach ($listings as $listing)
                    <tr class="hover:bg-brand-50/40">
                        <th scope="row" class="px-5 py-3.5 font-medium">
                            <a href="{{ route('admin.listings.show', $listing['id']) }}" class="hover:text-brand-700 hover:underline">{{ $listing['name'] }} {{ $listing['strength'] }}</a>
                            <span class="block text-xs font-normal text-ink-muted">{{ $listing['generic_name'] }} · {{ $listing['dosage_form'] }}</span>
                        </th>
                        <td class="px-5 py-3.5 whitespace-nowrap"><a href="{{ route('admin.users.show', $listing['owner']['id']) }}" class="text-ink-soft hover:text-brand-700 hover:underline">{{ $listing['owner']['name'] }}</a></td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $listing['district'] }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ config('medimatch.listing_types')[$listing['type']] }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ \Illuminate\Support\Carbon::parse($listing['expiry'].'-01')->format('M Y') }}</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$listing['status']" /></td>
                        <td class="px-5 py-3.5 tabular-nums">@if ($listing['reports_count']) <span class="font-semibold text-danger-700">{{ $listing['reports_count'] }}</span> @else 0 @endif</td>
                        <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.listings.show', $listing['id'])" size="sm" variant="secondary">Review</x-button></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-layouts.admin>
