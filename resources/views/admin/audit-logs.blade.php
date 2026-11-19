@php $areas = ['listing' => 'Listings', 'user' => 'Users', 'report' => 'Reports', 'verification' => 'Verification', 'announcement' => 'Announcements', 'settings' => 'Settings']; @endphp

<x-layouts.admin title="Audit logs" preview>
    <x-page-header title="Audit log" description="A permanent record of every administrative action: who did it, to what, and when. Entries cannot be edited or deleted." />

    <x-card class="mt-6">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" role="search" class="grid gap-4 sm:grid-cols-[1fr_13rem_auto] sm:items-end">
            <x-form.input name="q" label="Search" :value="$q" type="search" icon="magnifying-glass" placeholder="Person, action or record" maxlength="80" />
            <x-form.select name="area" label="Area" :selected="$area" placeholder="All areas" :options="$areas" />
            <div class="flex gap-2">
                <x-button type="submit">Filter</x-button>
                @if ($q !== '' || $area) <x-button :href="route('admin.audit-logs.index')" variant="ghost">Clear</x-button> @endif
            </div>
        </form>
    </x-card>

    <x-card class="mt-6" :padded="false">
        @if ($logs->isEmpty())
            <x-empty-state icon="document-magnifying-glass" title="No entries match" />
        @else
            <x-table :headers="['When', 'Who', 'Action', 'Record']" caption="Audit log entries">
                @foreach ($logs as $log)
                    <tr>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">
                            <time datetime="{{ $log['at']->toIso8601String() }}">{{ $log['at']->format('j M Y, g:i a') }}</time>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">{{ $log['actor'] }}</td>
                        <td class="px-5 py-3.5"><code class="rounded bg-canvas px-1.5 py-0.5 text-xs">{{ $log['action'] }}</code></td>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $log['target'] }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-layouts.admin>
