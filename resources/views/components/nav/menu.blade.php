{{--
    Renders grouped sidebar links from config/navigation.php.
    <x-nav.menu :groups="config('navigation.app')" variant="sidebar" />
--}}
@props(['groups', 'variant' => 'sidebar'])

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
