{{--
    Message box. Usage:
    <x-alert tone="success" title="Listing saved">It is now waiting for review.</x-alert>
    tone: info | success | warning | danger
--}}
@props(['tone' => 'info', 'title' => null, 'dismissible' => false])

@php
    $tones = [
        'info' => ['border-info-600/25 bg-info-50 text-info-700', 'information-circle'],
        'success' => ['border-success-600/25 bg-success-50 text-success-700', 'check-circle'],
        'warning' => ['border-warning-600/25 bg-warning-50 text-warning-700', 'exclamation-triangle'],
        'danger' => ['border-danger-600/25 bg-danger-50 text-danger-700', 'x-circle'],
    ];

    [$colours, $icon] = $tones[$tone] ?? $tones['info'];
@endphp

<div {{ $attributes->class(['flex items-start gap-3 rounded-card border p-4 text-sm', $colours]) }}
    role="{{ $tone === 'danger' ? 'alert' : 'status' }}" @if ($dismissible) data-dismissible @endif>
    <x-icon :name="$icon" class="mt-0.5 size-5" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="text-ink-soft">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" data-dismiss class="-m-1.5 rounded-control p-1.5 hover:bg-ink/5" aria-label="Dismiss message">
            <x-icon name="x-mark" class="size-4" />
        </button>
    @endif
</div>
