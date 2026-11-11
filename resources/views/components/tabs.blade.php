{{--
    Tab links with optional counts.
    <x-tabs :tabs="['all' => 'All', 'active' => 'Active']" :current="$tab" :counts="$counts" route="listings.index" param="tab" />
    The first tab is the default and is linked without a query string.
--}}
@props(['tabs', 'current', 'route', 'param' => 'tab', 'counts' => []])

<nav {{ $attributes->class(['-mb-px flex gap-1 overflow-x-auto border-b border-line']) }} aria-label="Filter">
    @foreach ($tabs as $key => $label)
        @php $active = (string) $current === (string) $key; @endphp
        <a href="{{ route($route, $loop->first ? [] : [$param => $key]) }}" @if ($active) aria-current="page" @endif
            @class([
                'inline-flex min-h-11 shrink-0 items-center gap-2 border-b-2 px-3.5 text-sm font-medium whitespace-nowrap',
                'border-brand-600 text-brand-800' => $active,
                'border-transparent text-ink-muted hover:border-line hover:text-ink' => ! $active,
            ])>
            {{ $label }}
            @isset($counts[$key])
                <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-brand-100 text-brand-800' => $active, 'bg-canvas text-ink-muted ring-1 ring-line ring-inset' => ! $active])>{{ $counts[$key] }}</span>
            @endisset
        </a>
    @endforeach
</nav>
