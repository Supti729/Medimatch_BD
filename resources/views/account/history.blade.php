@php
    $roleLabels = ['donated' => ['Donated', 'accent'], 'received' => ['Received', 'brand'], 'requested' => ['Requested', 'info']];
    $tabs = ['' => 'All'] + collect($roles)->mapWithKeys(fn ($r) => [$r => $roleLabels[$r][0]])->all();
@endphp

<x-layouts.app title="History" :preview="$usingSampleData">
    <x-page-header title="Interaction history" description="Your completed and cancelled donations and requests." />

    <x-tabs class="mt-6" route="history.index" param="role" :current="$role" :tabs="$tabs" />

    <x-card class="mt-6" :padded="false">
        @if ($entries->isEmpty())
            <x-empty-state icon="clock" title="Nothing here yet" description="Finished handovers and cancelled requests are kept here." />
        @else
            {{-- Table on larger screens --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line bg-canvas/60 text-xs font-semibold tracking-wide text-ink-muted uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Medicine</th>
                            <th scope="col" class="px-5 py-3">Type</th>
                            <th scope="col" class="px-5 py-3">With</th>
                            <th scope="col" class="px-5 py-3">Area</th>
                            <th scope="col" class="px-5 py-3">Date</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($entries as $entry)
                            <tr>
                                <th scope="row" class="px-5 py-4 font-medium">{{ $entry['medicine'] }}</th>
                                <td class="px-5 py-4"><x-badge :tone="$roleLabels[$entry['role']][1]">{{ $roleLabels[$entry['role']][0] }}</x-badge></td>
                                <td class="px-5 py-4 text-ink-soft">{{ $entry['with'] ?? '—' }}</td>
                                <td class="px-5 py-4 text-ink-soft">{{ $entry['area'] }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-ink-soft">{{ $entry['date']->format('j M Y') }}</td>
                                <td class="px-5 py-4"><x-status-badge :status="$entry['status']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Stacked cards on phones --}}
            <ul class="divide-y divide-line md:hidden">
                @foreach ($entries as $entry)
                    <li class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm font-semibold">{{ $entry['medicine'] }}</p>
                            <x-status-badge :status="$entry['status']" />
                        </div>
                        <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-muted">
                            <x-badge :tone="$roleLabels[$entry['role']][1]">{{ $roleLabels[$entry['role']][0] }}</x-badge>
                            @if ($entry['with']) <span>with {{ $entry['with'] }}</span> @endif
                            <span>{{ $entry['date']->format('j M Y') }}</span>
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layouts.app>
