{{--
    Page title row with optional action buttons.
    <x-page-header title="My listings" description="Medicines you have offered.">
        <x-button icon="plus">New listing</x-button>
    </x-page-header>
--}}
@props(['title', 'description' => null])

<div {{ $attributes->class(['flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1.5 max-w-2xl text-ink-soft">{{ $description }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
