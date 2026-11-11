{{--
    Card / panel.
    <x-card title="Recent activity" description="Your latest updates">
        <x-slot:actions><x-button size="sm">View all</x-button></x-slot:actions>
        ...content...
        <x-slot:footer>...</x-slot:footer>
    </x-card>
    Use :padded="false" for tables and lists that should touch the card edges.
--}}
@props(['title' => null, 'description' => null, 'padded' => true, 'as' => 'section'])

<{{ $as }} {{ $attributes->class(['rounded-card border border-line bg-surface shadow-card']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-base font-semibold">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-sm text-ink-muted">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-5 sm:p-6' => $padded])>{{ $slot }}</div>

    @isset($footer)
        <div class="rounded-b-card border-t border-line bg-canvas/60 px-5 py-3.5 sm:px-6">{{ $footer }}</div>
    @endisset
</{{ $as }}>
