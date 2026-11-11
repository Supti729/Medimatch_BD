{{--
    Heading block for a section of a public page.
    <x-section-heading eyebrow="How it works" title="Three simple steps" description="..." center />
--}}
@props(['title', 'eyebrow' => null, 'description' => null, 'center' => false, 'level' => 2])

<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $center]) }}>
    @if ($eyebrow)
        <p class="text-sm font-semibold tracking-wide text-brand-700 uppercase">{{ $eyebrow }}</p>
    @endif
    <h{{ $level }} @class(['text-2xl font-semibold tracking-tight sm:text-3xl', 'mt-2' => $eyebrow])>{{ $title }}</h{{ $level }}>
    @if ($description)
        <p class="mt-3 text-base text-ink-soft sm:text-lg">{{ $description }}</p>
    @endif
</div>
