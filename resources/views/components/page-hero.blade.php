{{-- Title band at the top of information pages. <x-page-hero title="About" description="..." /> --}}
@props(['title', 'description' => null, 'eyebrow' => null])

<div class="border-b border-line bg-surface">
    <div class="page-container py-10 sm:py-14">
        @if ($eyebrow)
            <p class="text-sm font-semibold tracking-wide text-brand-700 uppercase">{{ $eyebrow }}</p>
        @endif
        <h1 @class(['max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl', 'mt-2' => $eyebrow])>{{ $title }}</h1>
        @if ($description)
            <p class="mt-3 max-w-2xl text-lg text-ink-soft">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
