{{--
    Modal dialog, built on the browser's <dialog> element (focus trapping and Escape are built in).

    <x-button data-modal-open="report-listing">Report</x-button>

    <x-modal id="report-listing" title="Report this listing">
        ...content...
        <x-slot:footer>
            <x-button variant="secondary" data-modal-close>Cancel</x-button>
            <x-button>Send report</x-button>
        </x-slot:footer>
    </x-modal>

    size: sm | md | lg
--}}
@props(['id', 'title', 'size' => 'md'])

@php
    $sizes = ['sm' => 'max-w-sm', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl'];
@endphp

<dialog id="{{ $id }}" data-modal aria-labelledby="{{ $id }}-title"
    {{ $attributes->class(['m-auto w-[calc(100%-2rem)] rounded-card bg-surface p-0 text-ink shadow-overlay backdrop:bg-ink/50', $sizes[$size] ?? $sizes['md']]) }}>
    <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
        <h2 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h2>
        <button type="button" data-modal-close class="-m-1.5 rounded-control p-1.5 text-ink-muted hover:bg-canvas hover:text-ink" aria-label="Close">
            <x-icon name="x-mark" class="size-5" />
        </button>
    </div>

    <div class="px-5 py-5 text-sm text-ink-soft sm:px-6">{{ $slot }}</div>

    @isset($footer)
        <div class="flex flex-col-reverse gap-2 border-t border-line bg-canvas/60 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">{{ $footer }}</div>
    @endisset
</dialog>
