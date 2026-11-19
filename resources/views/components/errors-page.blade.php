{{-- Shared error page. Kept simple: no database queries, so it still works when something is broken. --}}
@props(['code', 'title', 'message', 'icon' => 'exclamation-triangle'])

<x-layouts.base :title="$title" noindex class="flex flex-col">
    <main id="main" class="flex flex-1 flex-col items-center justify-center px-4 py-16 text-center">
        <x-logo />
        <span class="mt-10 flex size-16 items-center justify-center rounded-full bg-brand-50 text-brand-700"><x-icon :name="$icon" class="size-8" /></span>
        <p class="mt-6 text-sm font-semibold text-brand-700">Error {{ $code }}</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $title }}</h1>
        <p class="mt-3 max-w-md text-ink-soft">{{ $message }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <x-button :href="url('/')" icon="home">Go to the home page</x-button>
            <x-button href="javascript:history.back()" variant="secondary" icon="arrow-left">Go back</x-button>
        </div>
    </main>
</x-layouts.base>
