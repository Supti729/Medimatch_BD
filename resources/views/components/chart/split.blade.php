{{--
    One bar split into parts of a whole, with a legend (label, value and share).
    <x-chart.split :data="['Donation' => 15, 'Affordable access' => 3]" :colours="['bg-chart-3', 'bg-chart-4']" unit="listings" />
--}}
@props(['data', 'colours' => ['bg-chart-1', 'bg-chart-2'], 'unit' => ''])

@php
    $total = array_sum($data);
    $colours = array_values($colours);
@endphp

<div {{ $attributes }}>
    @if ($total === 0)
        <p class="flex h-24 items-center justify-center rounded-control bg-canvas text-sm text-ink-muted">No data yet.</p>
    @else
        <p class="text-3xl font-semibold tracking-tight tabular-nums">{{ $total }} <span class="text-sm font-medium text-ink-muted">{{ $unit }}</span></p>

        {{-- 2px gaps in the surface colour separate the segments --}}
        <div class="mt-4 flex h-5 gap-0.5" role="img" aria-label="@foreach ($data as $label => $value){{ $label }}: {{ $value }} ({{ round($value / $total * 100) }}%){{ $loop->last ? '' : ', ' }}@endforeach">
            @foreach (array_values($data) as $i => $value)
                @if ($value > 0)
                    <span class="{{ $colours[$i] ?? 'bg-ink-muted' }} first:rounded-l last:rounded-r" style="width: {{ $value / $total * 100 }}%"
                        tabindex="0" data-tip="{{ array_keys($data)[$i] }}: {{ $value }} {{ $unit }} ({{ round($value / $total * 100) }}%)"></span>
                @endif
            @endforeach
        </div>

        <ul class="mt-4 space-y-2 text-sm">
            @foreach (array_keys($data) as $i => $label)
                <li class="flex items-center gap-2.5">
                    <span class="size-3 shrink-0 rounded-sm {{ $colours[$i] ?? 'bg-ink-muted' }}" aria-hidden="true"></span>
                    <span class="flex-1 text-ink-soft">{{ $label }}</span>
                    <span class="font-semibold tabular-nums">{{ $data[$label] }}</span>
                    <span class="w-10 text-right text-ink-muted tabular-nums">{{ round($data[$label] / $total * 100) }}%</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
