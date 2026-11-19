{{--
    Renders grouped sidebar links from config/navigation.php.
    <x-nav.menu :groups="config('navigation.app')" variant="sidebar" />
--}}
@props(['groups', 'variant' => 'sidebar'])

@php
    // Items may list the roles allowed to see them: 'roles' => ['admin', 'reviewer'].
    // Hiding a link is only for tidiness; the routes themselves are protected by middleware.
    $role = auth()->user()?->role?->value;
    $groups = collect($groups)
        ->map(fn ($items) => array_values(array_filter($items, fn ($item) => ! $role || ! isset($item['roles']) || in_array($role, $item['roles'], true))))
        ->filter()
        ->all();
@endphp

<nav {{ $attributes->class(['space-y-6']) }}>
    @foreach ($groups as $heading => $items)
        <div>
            <p @class([
                'px-3 text-xs font-semibold tracking-wider uppercase',
                'text-brand-300' => $variant === 'admin',
                'text-ink-muted' => $variant !== 'admin',
            ])>{{ $heading }}</p>
            <ul class="mt-2 space-y-0.5">
                @foreach ($items as $item)
                    <li>
                        <x-nav.link :route="$item['route']" :icon="$item['icon'] ?? null" :variant="$variant">{{ $item['label'] }}</x-nav.link>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
