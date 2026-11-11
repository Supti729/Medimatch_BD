{{-- Links between the developer preview pages --}}
@php
    $pages = [
        'styleguide' => 'Tokens',
        'styleguide.components' => 'Components',
        'styleguide.public' => 'Public layout',
        'styleguide.auth' => 'Auth layout',
        'styleguide.app' => 'User layout',
        'styleguide.admin' => 'Admin layout',
    ];
@endphp

<nav class="flex flex-wrap items-center gap-2 rounded-card border border-dashed border-brand-300 bg-brand-50 p-3 text-sm" aria-label="Developer previews">
    <span class="px-1 font-semibold text-brand-800">Dev previews:</span>
    @foreach ($pages as $route => $label)
        @if (Route::has($route))
            <a href="{{ route($route) }}" @class([
                'rounded-control px-2.5 py-1 font-medium',
                'bg-brand-600 text-white' => request()->routeIs($route),
                'text-brand-800 hover:bg-brand-100' => ! request()->routeIs($route),
            ])>{{ $label }}</a>
        @endif
    @endforeach
</nav>
