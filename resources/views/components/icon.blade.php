{{--
    Icon (Heroicons). Usage: <x-icon name="bell" class="size-5" />
    Icons are decorative by default; give buttons their own text or aria-label.
    Icon names: https://heroicons.com
--}}
@props(['name', 'solid' => false])

@php
    $svgAttributes = $attributes
        ->class([$attributes->has('class') ? '' : 'size-5', 'shrink-0'])
        ->merge(['aria-hidden' => 'true', 'focusable' => 'false'])
        ->getAttributes();
@endphp

{{ svg(($solid ? 'heroicon-s-' : 'heroicon-o-').$name, $svgAttributes) }}
