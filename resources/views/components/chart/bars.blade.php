{{--
    Horizontal bars for ranking categories (largest first).
    <x-chart.bars :data="['Dhaka' => 7, 'Sylhet' => 2]" colour="bg-chart-1" unit="listings" label-heading="District" />
--}}
@props(['data', 'colour' => 'bg-chart-1', 'unit' => '', 'labelHeading' => 'Category'])

@php $max = max(1, ...(array_values($data) ?: [1])); @endphp

<div {{ $attributes }}>
    @if (empty($data))
        <p class="flex h-32 items-center justify-center rounded-control bg-canvas text-sm text-ink-muted">No data yet.</p>
    @else
        <ul class="space-y-2.5">
            @foreach ($data as $label => $value)
                <li class="grid grid-cols-[6.5rem_1fr] items-center gap-3 text-sm" tabindex="0" data-tip="{{ $label }}: {{ $value }} {{ $unit }}">
                    <span class="truncate text-ink-soft">{{ $label }}</span>
                    <span class="flex items-center gap-2">
                        <span class="h-4 rounded-r {{ $colour }}" style="width: {{ max(1, $value / $max * 85) }}%" aria-hidden="true"></span>
                        <span class="text-xs font-semibold tabular-nums">{{ $value }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    <x-chart.table :data="$data" :label-heading="$labelHeading" :value-heading="ucfirst($unit) ?: 'Count'" />
</div>
