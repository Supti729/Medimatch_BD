{{--
    One number with a label, for dashboards.
    <x-stat-card label="Active listings" :value="$count" icon="gift" tone="brand" hint="2 pending review" />
    Pass real database values. If a value is not available yet, pass null and "—" is shown.
--}}
@props(['label', 'value' => null, 'icon' => null, 'tone' => 'brand', 'hint' => null, 'href' => null])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'accent' => 'bg-accent-50 text-accent-700',
        'success' => 'bg-success-50 text-success-700',
        'warning' => 'bg-warning-50 text-warning-700',
        'danger' => 'bg-danger-50 text-danger-700',
        'info' => 'bg-info-50 text-info-700',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['flex items-start justify-between gap-4 rounded-card border border-line bg-surface p-5 shadow-card', 'transition-shadow hover:shadow-raised' => $href]) }}>
    <div class="min-w-0">
        <p class="text-sm font-medium text-ink-muted">{{ $label }}</p>
        <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums">{{ $value ?? '—' }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-ink-muted">{{ $hint }}</p>
        @endif
    </div>
    @if ($icon)
        <span class="flex size-10 shrink-0 items-center justify-center rounded-control {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" class="size-5" />
        </span>
    @endif
</{{ $tag }}>
