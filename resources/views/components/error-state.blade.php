{{--
    Shown when something could not be loaded.
    <x-error-state title="Search is unavailable" description="Please try again in a moment.">
        <x-button variant="secondary" href="{{ url()->current() }}">Try again</x-button>
    </x-error-state>
--}}
@props(['title' => 'Something went wrong', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center px-6 py-12 text-center']) }} role="alert">
    <span class="flex size-14 items-center justify-center rounded-full bg-danger-50 text-danger-600">
        <x-icon name="exclamation-triangle" class="size-7" />
    </span>
    <h3 class="mt-4 text-base font-semibold">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-ink-muted">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
