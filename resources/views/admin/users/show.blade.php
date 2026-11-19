@php
    $staff = $user->role->isStaff();
    $self = $user->id === auth()->id();
    // Admins see only what moderation needs: contact details are partly hidden.
    $maskedEmail = $user->email ? Str::mask($user->email, '*', 2, max(1, strpos($user->email, '@') - 2)) : null;
    $maskedPhone = $user->phone ? Str::mask($user->phone, '*', 3, 5) : null;
@endphp

<x-layouts.admin :title="$user->name">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" /> Users</a>

    <x-page-header class="mt-3" :title="$user->name" :description="'User #'.$user->id.' · joined '.$user->created_at->format('j M Y')">
        <x-status-badge :status="$user->status" />
        <x-badge>{{ $user->role->label() }}</x-badge>
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <x-card title="Account details" description="Contact details are partly hidden. Full details are not needed for moderation.">
                <x-detail-list :columns="3" :items="[
                    'Email' => $maskedEmail,
                    'Phone number' => $maskedPhone,
                    'Area' => $user->areaLabel(),
                    'Verified' => $user->isVerified() ? 'Yes' : 'No',
                    'Listings' => $user->listings_count,
                    'Requests' => $user->medicine_requests_count,
                    'Last login' => $user->last_login_at?->diffForHumans() ?? 'Never',
                    'Reports about this user' => $reports->count(),
                ]" />
            </x-card>

            <x-card title="Recent listings" :padded="false">
                @if ($listings->isEmpty())
                    <p class="p-5 text-sm text-ink-muted sm:p-6">This user has no listings.</p>
                @else
                    <x-table :headers="['Medicine', 'Status', 'Created', '']">
                        @foreach ($listings as $listing)
                            <tr>
                                <th scope="row" class="px-5 py-3.5 font-medium">{{ $listing->brand_name }} {{ $listing->medicine->strength }}</th>
                                <td class="px-5 py-3.5"><x-status-badge :status="$listing->status" /></td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $listing->created_at->format('j M Y') }}</td>
                                <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.listings.show', $listing)" size="sm" variant="secondary">Review</x-button></td>
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
                            <li><a href="{{ route('admin.reports.show', $report) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm hover:bg-brand-50/60 sm:px-6">
                                <span class="min-w-0"><span class="block font-medium">{{ $report->reasonLabel() }}</span><span class="block truncate text-xs text-ink-muted">About a {{ $report->target_type }} · {{ $report->created_at->diffForHumans() }}</span></span>
                                <x-status-badge :status="$report->status" />
                            </a></li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            @if ($staff || $self)
                <x-card title="Account access">
                    <x-alert tone="info">{{ $self ? 'You cannot change your own account here.' : 'Administrator and reviewer accounts cannot be changed from the admin panel.' }}</x-alert>
                </x-card>
            @else
                <x-admin.decision title="Account access" route="admin.users.status" :param="$user->id" feature="Changing account access"
                    :submit="$user->isSuspended() ? 'Restore account' : 'Suspend account'"
                    note-hint="Recorded in the audit log."
                    :options="$user->isSuspended()
                        ? ['restore' => ['Restore', 'The user can log in again and their listings become visible.']]
                        : ['suspend' => ['Suspend', 'The user is logged out and cannot log in. Their listings are hidden and anyone waiting for them is released.']]" />

                <x-card title="Role">
                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="space-y-4" data-loading>
                        @csrf
                        <x-form.select name="role" label="Role" :selected="$user->role->value" required
                            :options="['user' => 'User', 'partner' => 'Partner organization', 'reviewer' => 'Authorized reviewer']"
                            hint="Administrator access cannot be given here." />
                        <x-form.input name="note" id="role-note" label="Reason" required maxlength="1000" />
                        <x-button type="submit" variant="secondary" block>Change role</x-button>
                    </form>
                </x-card>
            @endif

            <x-admin.audit-list :entries="$audit" title="Actions on this account" />
        </aside>
    </div>
</x-layouts.admin>
