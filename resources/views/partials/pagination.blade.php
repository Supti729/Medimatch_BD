{{-- Pagination in the Medimatch BD style. Usage: {{ $listings->links('partials.pagination') }} --}}
@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Pagination">
        <p class="text-sm text-ink-muted">
            Showing <span class="font-medium text-ink">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-ink">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-ink">{{ $paginator->total() }}</span>
        </p>

        <ul class="flex items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="inline-flex min-h-10 items-center gap-1 rounded-control px-3 text-sm font-medium text-ink-muted/60" aria-disabled="true">
                        <x-icon name="chevron-left" class="size-4" /> Previous
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-10 items-center gap-1 rounded-control border border-line bg-surface px-3 text-sm font-medium hover:bg-brand-50">
                        <x-icon name="chevron-left" class="size-4" /> Previous
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="hidden px-1 text-ink-muted sm:block" aria-hidden="true">{{ $element }}</li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="hidden sm:block">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex size-10 items-center justify-center rounded-control bg-brand-600 text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Go to page {{ $page }}" class="inline-flex size-10 items-center justify-center rounded-control text-sm font-medium text-ink-soft hover:bg-brand-50">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-10 items-center gap-1 rounded-control border border-line bg-surface px-3 text-sm font-medium hover:bg-brand-50">
                        Next <x-icon name="chevron-right" class="size-4" />
                    </a>
                @else
                    <span class="inline-flex min-h-10 items-center gap-1 rounded-control px-3 text-sm font-medium text-ink-muted/60" aria-disabled="true">
                        Next <x-icon name="chevron-right" class="size-4" />
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
