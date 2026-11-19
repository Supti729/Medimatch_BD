@php $roleTones = ['user' => 'neutral', 'partner' => 'info', 'reviewer' => 'brand', 'admin' => 'accent']; @endphp

<x-layouts.admin title="Users" preview>
    <x-page-header title="User management" description="Search accounts, review their activity, and suspend or restore access." />

    <x-card class="mt-6">
        <form method="GET" action="{{ route('admin.users.index') }}" role="search" class="grid gap-4 sm:grid-cols-[1fr_11rem_11rem_auto] sm:items-end">
            <x-form.input name="q" label="Search" :value="$q" type="search" icon="magnifying-glass" placeholder="Name, email or district" maxlength="80" />
            <x-form.select name="status" label="Status" :selected="$status" placeholder="Any status" :options="['active' => 'Active', 'suspended' => 'Suspended']" />
            <x-form.select name="role" label="Role" :selected="$role" placeholder="Any role" :options="['user' => 'User', 'partner' => 'Partner', 'reviewer' => 'Reviewer', 'admin' => 'Administrator']" />
            <div class="flex gap-2">
                <x-button type="submit">Filter</x-button>
                @if ($q !== '' || $status || $role)
                    <x-button :href="route('admin.users.index')" variant="ghost">Clear</x-button>
                @endif
            </div>
        </form>
    </x-card>

    <x-card class="mt-6" :padded="false">
        <p class="border-b border-line px-5 py-3 text-sm text-ink-soft sm:px-6" role="status"><span class="font-semibold text-ink">{{ $users->count() }}</span> {{ Str::plural('account', $users->count()) }}</p>

        @if ($users->isEmpty())
            <x-empty-state icon="users" title="No accounts match" description="Try a different search or clear the filters." />
        @else
            <x-table :headers="['User', 'District', 'Role', 'Status', 'Listings', 'Requests', 'Reports', 'Joined', '']" caption="User accounts">
                @foreach ($users as $user)
                    <tr class="hover:bg-brand-50/40">
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">
                            <a href="{{ route('admin.users.show', $user['id']) }}" class="hover:text-brand-700 hover:underline">{{ $user['name'] }}</a>
                        </th>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $user['district'] }}</td>
                        <td class="px-5 py-3.5"><x-badge :tone="$roleTones[$user['role']] ?? 'neutral'">{{ ucfirst($user['role']) }}</x-badge></td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$user['status']" /></td>
                        <td class="px-5 py-3.5 tabular-nums">{{ $user['listings_count'] }}</td>
                        <td class="px-5 py-3.5 tabular-nums">{{ $user['requests_count'] }}</td>
                        <td class="px-5 py-3.5 tabular-nums">@if ($user['reports_count']) <span class="font-semibold text-danger-700">{{ $user['reports_count'] }}</span> @else 0 @endif</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $user['joined']->format('j M Y') }}</td>
                        <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.users.show', $user['id'])" size="sm" variant="secondary">View</x-button></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-layouts.admin>
