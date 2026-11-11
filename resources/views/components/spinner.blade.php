{{-- Loading spinner. Usage: <x-spinner /> or <x-spinner label="Loading listings" /> --}}
@props(['label' => null])

<svg {{ $attributes->class([$attributes->has('class') ? '' : 'size-5', 'animate-spin']) }} viewBox="0 0 24 24" fill="none"
    @if ($label) role="status" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25" />
    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
</svg>
