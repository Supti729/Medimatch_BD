{{--
    Column chart for one series over time.
    <x-chart.columns :data="['Jun' => 2, 'Jul' => 5]" colour="bg-chart-1" unit="users" label-heading="Month" />

    Built with plain HTML and CSS (no chart library). Each column has a hover/focus tooltip,
    the tallest column is labelled, and the full data is in "View as table".
--}}
@props(['data', 'colour' => 'bg-chart-1', 'unit' => '', 'labelHeading' => 'Month'])

@php
    $max = max(1, ...array_values($data));

    // Round the axis top up to a clean number (1, 2, 4, 5, 10, 20, 50 ...).
    $magnitude = 10 ** floor(log10($max));
    $axisMax = collect([1, 2, 4, 5, 10])->map(fn ($step) => $step * $magnitude)->first(fn ($value) => $value >= $max);
    $axisMax = (int) max(2, $axisMax);

    $peak = array_search(max($data), $data, true);
@endphp

<div {{ $attributes }}>
    @if (array_sum($data) === 0)
        <p class="flex h-44 items-center justify-center rounded-control bg-canvas text-sm text-ink-muted">No data for this period yet.</p>
    @else
        <div class="flex gap-3">
            <div class="flex h-44 flex-col justify-between text-right text-xs text-ink-muted tabular-nums" aria-hidden="true">
                <span class="-translate-y-1/2">{{ $axisMax }}</span>
                <span>{{ $axisMax / 2 }}</span>
                <span class="translate-y-1/2">0</span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="relative h-44 border-b border-line">
                    <span class="absolute inset-x-0 top-0 border-t border-line/70" aria-hidden="true"></span>
                    <span class="absolute inset-x-0 top-1/2 border-t border-line/70" aria-hidden="true"></span>

                    <ul class="absolute inset-0 flex">
                        @foreach ($data as $label => $value)
                            <li class="flex h-full flex-1 items-end justify-center">
                                <span class="relative flex h-full w-6 items-end justify-center" tabindex="0" role="img"
                                    aria-label="{{ $label }}: {{ $value }} {{ $unit }}" data-tip="{{ $label }}: {{ $value }} {{ $unit }}">
                                    @if ($label === $peak)
                                        <span class="absolute text-xs font-semibold text-ink tabular-nums" style="bottom: calc({{ $value / $axisMax * 100 }}% + 4px)" aria-hidden="true">{{ $value }}</span>
                                    @endif
                                    <span class="w-full rounded-t {{ $colour }}" style="height: {{ $value / $axisMax * 100 }}%"></span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <ul class="mt-2 flex text-xs text-ink-muted" aria-hidden="true">
                    @foreach ($data as $label => $value)
                        <li class="flex-1 text-center">{{ $label }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <x-chart.table :data="$data" :label-heading="$labelHeading" :value-heading="ucfirst($unit) ?: 'Count'" />
</div>
