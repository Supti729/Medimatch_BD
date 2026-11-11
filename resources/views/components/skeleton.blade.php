{{-- Grey placeholder shown while content loads. Usage: <x-skeleton :lines="3" /> --}}
@props(['lines' => 3])

<div {{ $attributes->class(['animate-pulse space-y-3']) }} role="status" aria-label="Loading">
    <div class="h-4 w-2/5 rounded bg-line"></div>
    @for ($i = 0; $i < $lines; $i++)
        <div @class(['h-3 rounded bg-line', 'w-full' => $i % 2 === 0, 'w-4/5' => $i % 2 === 1])></div>
    @endfor
</div>
