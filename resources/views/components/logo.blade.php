{{-- Medimatch BD logo. Usage: <x-logo /> or <x-logo tone="light" /> on dark backgrounds --}}
@props(['tone' => 'dark', 'href' => null])

@php
    $href ??= Route::has('home') ? route('home') : url('/');
    $text = $tone === 'light' ? 'text-white' : 'text-ink';
    $suffix = $tone === 'light' ? 'text-brand-300' : 'text-brand-600';
@endphp

<a href="{{ $href }}" {{ $attributes->class(['inline-flex items-center gap-2.5 rounded-control']) }} aria-label="{{ config('app.name') }} home">
    <svg class="size-9 shrink-0" viewBox="0 0 36 36" fill="none" aria-hidden="true">
        <rect width="36" height="36" rx="10" class="fill-brand-600" />
        {{-- A capsule: one half solid, one half outlined --}}
        <g transform="rotate(-45 18 18)">
            <rect x="7" y="12.5" width="22" height="11" rx="5.5" stroke="white" stroke-width="2" />
            <path d="M18 12.5h5.5a5.5 5.5 0 0 1 0 11H18v-11Z" fill="white" />
        </g>
    </svg>
    <span class="text-lg font-bold tracking-tight whitespace-nowrap {{ $text }}">Medimatch <span class="{{ $suffix }}">BD</span></span>
</a>
