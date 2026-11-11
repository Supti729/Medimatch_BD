{{-- One row inside <x-dropdown>. Renders a link when "href" is given, otherwise a button. --}}
@props(['href' => null, 'icon' => null, 'type' => 'button'])

@php
    $classes = 'flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-ink-soft hover:bg-brand-50 hover:text-brand-800 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent disabled:hover:text-ink-soft';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-icon :name="$icon" class="size-4" /> @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-icon :name="$icon" class="size-4" /> @endif
        {{ $slot }}
    </button>
@endif
