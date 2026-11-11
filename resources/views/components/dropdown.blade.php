{{--
    Dropdown menu.
    <x-dropdown>
        <x-slot:trigger>
            <button type="button" data-dropdown-trigger aria-haspopup="true" aria-expanded="false">Menu</button>
        </x-slot:trigger>
        <x-dropdown.item href="/profile" icon="user">Profile</x-dropdown.item>
    </x-dropdown>
    The trigger element must have the data-dropdown-trigger attribute.
--}}
@props(['align' => 'right', 'width' => 'w-56'])

<div class="relative" data-dropdown>
    {{ $trigger }}
    <div data-dropdown-menu
        {{ $attributes->class(['absolute z-40 mt-2 hidden overflow-hidden rounded-card border border-line bg-surface py-1.5 shadow-raised', $width, $align === 'left' ? 'left-0' : 'right-0']) }}>
        {{ $slot }}
    </div>
</div>
