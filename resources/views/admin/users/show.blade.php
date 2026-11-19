@php
    $connected = Route::has('admin.users.status');
    $staff = in_array($user['role'], ['admin', 'reviewer'], true);

    // Admins see only what they need: contact details are partly hidden.
    $maskedEmail = Str::mask($user['email'], '*', 2, max(1, strpos($user['email'], '@') - 2));
    $maskedPhone = Str::mask($user['phone'], '*', 3, 5);
@endphp

<x-layouts.admin :title="$user['name']" preview>
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" /> Users</a>

    <x-page-header class="mt-3" :title="$user['name']" :description="'User #'.$user['id'].' · joined '.$user['joined']->format('j M Y')">
        <x-status-badge :status="$user['status']" />
        <x-badge>{{ ucfirst($user['role']) }}</x-badge>
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <x-card title="Account details" description="Contact details are partly hidden. Full details are not needed for moderation.">
                <x-detail-list :columns="3" :items="[
                    'Email' => $maskedEmail,
                    'Phone number' => $maskedPhone,
                    'District' => $user['district'],
                    'Listings' => $user['listings_count'],
                    'Requests' => $user['requests_count'],
                    'Reports against this user' => $user['reports_count'],
                ]" />
            </x-card>

            <x-card title="Listings by this user" :padded="false">
                @if ($listings->isEmpty())
                    <p class="p-5 text-sm text-ink-muted sm:p-6">This user has no listings in the sample data.</p>
                @else
                    <x-table :headers="['Medicine', 'Status', 'Listed', '']">
                        @foreach ($listings as $listing)
                            <tr>
                                <th scope="row" class="px-5 py-3.5 font-medium">{{ $listing['name'] }} {{ $listing['strength'] }}</th>
                                <td class="px-5 py-3.5"><x-status-badge :status="$listing['status']" /></td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $listing['posted_at']->format('j M Y') }}</td>
                                <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.listings.show', $listing['id'])" size="sm" variant="secondary">Review</x-button></td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>

            <x-card title="Reports about this user" :padded="false">
                @if ($reports->isEmpty())
                    <p class="p-5 text-sm text-ink-muted sm:p-6">No reports have been made about this user.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($reports as $report)
                            <li><a href="{{ route('admin.reports.show', $report['id']) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm hover:bg-brand-50/60 sm:px-6">
                                <span class="min-w-0"><span class="block font-medium">{{ $report['reason'] }}</span><span class="block truncate text-xs text-ink-muted">{{ $report['target'] }} · {{ $report['created_at']->diffForHumans() }}</span></span>
                                <x-status-badge :status="$report['status']" />
                            </a></li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Account access">
                @if ($staff)
                    <x-alert tone="info">Administrator and reviewer accounts cannot be suspended from this page.</x-alert>
                @else
                    <p class="text-sm text-ink-soft">
                        @if ($user['status'] === 'suspended')
                            This account is suspended. The user cannot log in, and their listings are hidden.
                        @else
                            Suspending stops the user from logging in and hides their listings. It can be undone.
                        @endif
                    </p>
                    <x-not-connected route="admin.users.status" feature="Changing account access" class="mt-4" />
                    <div class="mt-4">
                        @if ($user['status'] === 'suspended')
                            <x-button block :disabled="! $connected" icon="arrow-path">Restore account</x-button>
                        @else
                            <x-button block variant="danger" :disabled="! $connected" icon="no-symbol">Suspend account</x-button>
                        @endif
                    </div>
                    <p class="mt-3 text-xs text-ink-muted">A reason is required and is written to the audit log.</p>
                @endif
            </x-card>

            <x-admin.audit-list :entries="$audit" title="Actions on this account" />
        </aside>
    </div>
</x-layouts.admin>
