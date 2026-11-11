{{--
    Navigation link that knows whether its page exists yet.

    <x-nav.link route="dashboard" icon="home" variant="sidebar">Dashboard</x-nav.link>

    - Route exists       -> a normal link, highlighted when it is the current page.
    - Route not built yet -> greyed-out text marked "Soon" (not a dead link).

    variant: header | sidebar | admin | bottom | footer | drawer
--}}
@props(['route', 'icon' => null, 'variant' => 'header'])

@php
    $exists = Route::has($route);

    // "listings.index" is also active on listings.create, listings.edit, ...
    $group = str_ends_with($route, '.index') ? substr($route, 0, -6) : null;
    $active = $exists && (request()->routeIs($route) || ($group && request()->routeIs($group.'.*')));

    $styles = [
        'header' => [
            'base' => 'inline-flex items-center gap-2 rounded-control px-3 py-2 text-sm font-medium',
            'idle' => 'text-ink-soft hover:bg-brand-50 hover:text-brand-800',
            'active' => 'bg-brand-50 text-brand-800',
        ],
        'drawer' => [
            'base' => 'flex min-h-11 items-center gap-3 rounded-control px-3 py-2.5 text-base font-medium',
            'idle' => 'text-ink-soft hover:bg-brand-50 hover:text-brand-800',
            'active' => 'bg-brand-50 text-brand-800',
        ],
        'sidebar' => [
            'base' => 'flex min-h-10 items-center gap-3 rounded-control px-3 py-2 text-sm font-medium',
            'idle' => 'text-ink-soft hover:bg-brand-50 hover:text-brand-800',
            'active' => 'bg-brand-50 text-brand-800',
        ],
        'admin' => [
            'base' => 'flex min-h-10 items-center gap-3 rounded-control px-3 py-2 text-sm font-medium',
            'idle' => 'text-brand-100 hover:bg-white/10 hover:text-white',
            'active' => 'bg-white/15 text-white',
        ],
        'bottom' => [
            'base' => 'flex min-h-14 flex-1 flex-col items-center justify-center gap-0.5 px-1 text-[0.6875rem] font-medium',
            'idle' => 'text-ink-muted hover:text-brand-700',
            'active' => 'text-brand-700',
        ],
        'footer' => [
            'base' => 'inline-flex items-center gap-2 text-sm',
            'idle' => 'text-ink-soft hover:text-brand-700 hover:underline',
            'active' => 'text-brand-700',
        ],
    ];

    $style = $styles[$variant] ?? $styles['header'];
    $disabled = $variant === 'admin' ? 'cursor-not-allowed text-brand-100/60' : 'cursor-not-allowed text-ink-muted/80';
    $iconSize = $variant === 'bottom' ? 'size-6' : 'size-5';
@endphp

@if ($exists)
    <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
        {{ $attributes->class([$style['base'], $active ? $style['active'] : $style['idle']]) }}>
        @if ($icon) <x-icon :name="$icon" :class="$iconSize" /> @endif
        <span @class(['truncate' => $variant !== 'footer'])>{{ $slot }}</span>
    </a>
@else
    <span aria-disabled="true" title="This page is not built yet" {{ $attributes->class([$style['base'], $disabled]) }}>
        @if ($icon) <x-icon :name="$icon" :class="$iconSize" /> @endif
        <span @class(['truncate' => $variant !== 'footer'])>{{ $slot }}</span>
        @if ($variant !== 'bottom')
            <span @class([
                'ml-auto rounded-full px-1.5 py-0.5 text-[0.625rem] font-semibold tracking-wide uppercase',
                'bg-white/10 text-brand-100' => $variant === 'admin',
                'bg-canvas text-ink-muted ring-1 ring-line ring-inset' => $variant !== 'admin',
            ])>Soon</span>
        @endif
    </span>
@endif
