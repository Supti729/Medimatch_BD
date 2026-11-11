{{--
    Shown when a list has nothing in it yet.
    <x-empty-state icon="gift" title="No listings yet" description="Offer a medicine you no longer need.">
        <x-button icon="plus">Create listing</x-button>
    </x-empty-state>
--}}
@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-700">
        <x-icon :name="$icon" class="size-7" />
    </span>
    <h3 class="mt-4 text-base font-semibold">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-ink-muted">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
