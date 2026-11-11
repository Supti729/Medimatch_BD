{{--
    Small label. Usage: <x-badge tone="success">Verified</x-badge>
    tone: neutral | brand | accent | success | warning | danger | info
--}}
@props(['tone' => 'neutral', 'dot' => false])

@php
    $tones = [
        'neutral' => ['bg-canvas text-ink-soft ring-ink/15', 'bg-ink-muted'],
        'brand' => ['bg-brand-50 text-brand-800 ring-brand-600/20', 'bg-brand-600'],
        'accent' => ['bg-accent-50 text-accent-700 ring-accent-600/20', 'bg-accent-600'],
        'success' => ['bg-success-50 text-success-700 ring-success-600/20', 'bg-success-600'],
        'warning' => ['bg-warning-50 text-warning-700 ring-warning-600/20', 'bg-warning-600'],
        'danger' => ['bg-danger-50 text-danger-700 ring-danger-600/20', 'bg-danger-600'],
        'info' => ['bg-info-50 text-info-700 ring-info-600/20', 'bg-info-600'],
    ];

    [$colours, $dotColour] = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap ring-1 ring-inset', $colours]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dotColour }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
