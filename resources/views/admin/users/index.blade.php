<x-layouts.admin title="Users">
    <x-page-header title="User management" description="Search accounts, review their activity, and suspend or restore access." />

    <x-card class="mt-6">
        <form method="GET" action="{{ route('admin.users.index') }}" role="search" class="grid gap-4 sm:grid-cols-[1fr_11rem_11rem_auto] sm:items-end">
            <x-form.input name="q" label="Search" :value="$q" type="search" icon="magnifying-glass" placeholder="Name, email or phone" maxlength="80" />
            <x-form.select name="status" label="Status" :selected="$status" placeholder="Any status" :options="['active' => 'Active', 'suspended' => 'Suspended']" />
            <x-form.select name="role" label="Role" :selected="$role" placeholder="Any role" :options="collect(\App\Enums\Role::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" />
            <div class="flex gap-2">
                <x-button type="submit">Filter</x-button>
                @if ($q !== '' || $status || $role)
                    <x-button :href="route('admin.users.index')" variant="ghost">Clear</x-button>
                @endif
            </div>
        </form>
    </x-card>

    <x-card class="mt-6" :padded="false">
        <p class="border-b border-line px-5 py-3 text-sm text-ink-soft sm:px-6" role="status"><span class="font-semibold text-ink">{{ $users->total() }}</span> {{ Str::plural('account', $users->total()) }}</p>

        @if ($users->isEmpty())
            <x-empty-state icon="users" title="No accounts match" description="Try a different search or clear the filters." />
        @else
            <x-table :headers="['User', 'District', 'Role', 'Status', 'Listings', 'Requests', 'Reports', 'Joined', '']" caption="User accounts">
                @foreach ($users as $user)
                    <tr class="hover:bg-brand-50/40">
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">
                            <a href="{{ route('admin.users.show', $user) }}" class="hover:text-brand-700 hover:underline">{{ $user->name }}</a>
                        </th>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $user->district?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5"><x-badge :tone="['user' => 'neutral', 'partner' => 'info', 'reviewer' => 'brand', 'admin' => 'accent'][$user->role->value]">{{ $user->role->label() }}</x-badge></td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$user->status" /></td>
                        <td class="px-5 py-3.5 tabular-nums">{{ $user->listings_count }}</td>
                        <td class="px-5 py-3.5 tabular-nums">{{ $user->medicine_requests_count }}</td>
                        <td class="px-5 py-3.5 tabular-nums">@if ($reportCounts[$user->id] ?? 0) <span class="font-semibold text-danger-700">{{ $reportCounts[$user->id] }}</span> @else 0 @endif</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $user->created_at->format('j M Y') }}</td>
                        <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.users.show', $user)" size="sm" variant="secondary">View</x-button></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
    <div class="mt-6">{{ $users->links('partials.pagination') }}</div>
</x-layouts.admin>
