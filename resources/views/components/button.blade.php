{{--
    Button. Renders a link when "href" is given, otherwise a <button>.

    <x-button>Save</x-button>
    <x-button variant="secondary" icon="plus" href="/listings/create">New listing</x-button>
    <x-button type="submit">Send</x-button>   (inside <form data-loading> it shows a spinner while sending)

    variant: primary | secondary | accent | ghost | danger
    size:    sm | md | lg
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
    'block' => false,
])

@php
    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-card hover:bg-brand-700 active:bg-brand-800',
        'secondary' => 'border border-line bg-surface text-ink shadow-card hover:border-brand-300 hover:bg-brand-50',
        'accent' => 'bg-accent-600 text-white shadow-card hover:bg-accent-700',
        'ghost' => 'text-ink-soft hover:bg-brand-50 hover:text-brand-800',
        'danger' => 'bg-danger-600 text-white shadow-card hover:bg-danger-700',
    ];

    $sizes = [
        'sm' => 'min-h-9 gap-1.5 px-3 text-sm',
        'md' => 'min-h-11 gap-2 px-4 text-sm',
        'lg' => 'min-h-12 gap-2 px-6 text-base',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-control font-semibold transition-colors',
        'disabled:cursor-not-allowed disabled:opacity-60',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $block ? 'w-full' : '',
    ]);

    $iconSize = $size === 'lg' ? 'size-5' : 'size-4';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-icon :name="$icon" :class="$iconSize" /> @endif
        {{ $slot }}
        @if ($iconRight) <x-icon :name="$iconRight" :class="$iconSize" /> @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        <x-spinner data-spinner class="hidden {{ $iconSize }}" />
        @if ($icon) <x-icon :name="$icon" :class="$iconSize" /> @endif
        {{ $slot }}
        @if ($iconRight) <x-icon :name="$iconRight" :class="$iconSize" /> @endif
    </button>
@endif
