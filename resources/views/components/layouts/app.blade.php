{{--
    Layout for signed-in users: sidebar on desktop, drawer + bottom bar on phones.
    <x-layouts.app title="My listings"> ...page content... </x-layouts.app>
--}}
@props(['title' => null, 'unread' => 0])

@php
    // "unread" is the unread notification count. It stays 0 until the
    // notification system is built in the backend part (push B4).
    $user = auth()->user();
    $displayName = $user?->name ?? 'Guest';
    $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
    $unread = (int) $unread;
@endphp

<x-layouts.base :title="$title" noindex>
    {{-- Desktop sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-line bg-surface lg:flex">
        <div class="flex h-16 shrink-0 items-center border-b border-line px-5">
            <x-logo />
        </div>
        <div class="flex-1 overflow-y-auto px-3 py-5 [scrollbar-width:thin]">
            <x-nav.menu :groups="config('navigation.app')" variant="sidebar" aria-label="Account" />
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div id="app-drawer" data-drawer class="fixed inset-0 z-50 hidden lg:hidden">
        <button type="button" data-toggle="#app-drawer" class="absolute inset-0 size-full cursor-default bg-ink/50" aria-label="Close menu" tabindex="-1"></button>
        <div class="relative flex h-full w-72 max-w-[85%] flex-col bg-surface shadow-overlay">
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-4">
                <x-logo />
                <button type="button" data-toggle="#app-drawer" class="inline-flex size-11 items-center justify-center rounded-control text-ink-soft hover:bg-brand-50">
                    <span class="sr-only">Close menu</span>
                    <x-icon name="x-mark" class="size-6" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-3 py-5 [scrollbar-width:thin]">
                <x-nav.menu :groups="config('navigation.app')" variant="sidebar" aria-label="Account (mobile)" />
            </div>
        </div>
    </div>

    <div class="flex min-h-screen flex-col lg:pl-64">
        <header class="sticky top-0 z-20 border-b border-line bg-surface/95 backdrop-blur">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button type="button" data-toggle="#app-drawer" aria-expanded="false" aria-controls="app-drawer"
                    class="-ml-2 inline-flex size-11 items-center justify-center rounded-control text-ink-soft hover:bg-brand-50 lg:hidden">
                    <span class="sr-only">Open menu</span>
                    <x-icon name="bars-3" class="size-6" />
                </button>

                <x-logo class="lg:hidden" />

                {{-- Medicine search (appears once the browse page exists) --}}
                <div class="hidden flex-1 md:block">
                    @if (Route::has('medicines.index'))
                        <form method="GET" action="{{ route('medicines.index') }}" role="search" class="relative max-w-md">
                            <label for="header-search" class="sr-only">Search medicines</label>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-ink-muted">
                                <x-icon name="magnifying-glass" class="size-5" />
                            </span>
                            <input id="header-search" type="search" name="q" placeholder="Search medicines…" autocomplete="off"
                                class="block min-h-10 w-full rounded-control border border-line bg-canvas py-2 pr-3.5 pl-11 text-sm placeholder:text-ink-muted focus:border-brand-600 focus:bg-surface focus:outline-2 focus:outline-offset-0 focus:outline-brand-600/30">
                        </form>
                    @endif
                </div>

                <div class="ml-auto flex items-center gap-1 sm:gap-2">
                    {{-- Notifications --}}
                    <x-dropdown width="w-80 max-w-[calc(100vw-2rem)]">
                        <x-slot:trigger>
                            <button type="button" data-dropdown-trigger aria-haspopup="true" aria-expanded="false"
                                class="relative inline-flex size-11 items-center justify-center rounded-control text-ink-soft hover:bg-brand-50">
                                <span class="sr-only">Notifications{{ $unread ? ', '.$unread.' unread' : '' }}</span>
                                <x-icon name="bell" class="size-6" />
                                @if ($unread)
                                    <span class="absolute top-1.5 right-1.5 flex min-w-5 items-center justify-center rounded-full bg-accent-600 px-1 text-[0.6875rem] font-semibold text-white ring-2 ring-surface" aria-hidden="true">
                                        {{ $unread > 99 ? '99+' : $unread }}
                                    </span>
                                @endif
                            </button>
                        </x-slot:trigger>

                        <div class="flex items-center justify-between border-b border-line px-4 pt-1 pb-3">
                            <p class="text-sm font-semibold">Notifications</p>
                            @if (Route::has('notifications.index'))
                                <a href="{{ route('notifications.index') }}" class="text-sm font-medium text-brand-700 hover:underline">View all</a>
                            @endif
                        </div>
                        @isset($notifications)
                            {{ $notifications }}
                        @else
                            <x-empty-state icon="bell" title="No notifications yet" description="Updates about your listings, requests and messages will appear here." class="py-8" />
                        @endisset
                    </x-dropdown>

                    {{-- Account menu --}}
                    <x-dropdown>
                        <x-slot:trigger>
                            <button type="button" data-dropdown-trigger aria-haspopup="true" aria-expanded="false"
                                class="inline-flex min-h-11 items-center gap-2 rounded-control py-1 pr-2 pl-1 hover:bg-brand-50">
                                <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white" aria-hidden="true">{{ $initial }}</span>
                                <span class="hidden max-w-32 truncate text-sm font-medium sm:block">{{ $displayName }}</span>
                                <x-icon name="chevron-down" class="hidden size-4 text-ink-muted sm:block" />
                                <span class="sr-only">Open account menu</span>
                            </button>
                        </x-slot:trigger>

                        <div class="border-b border-line px-4 pt-1 pb-3">
                            <p class="truncate text-sm font-semibold">{{ $displayName }}</p>
                            <p class="text-xs text-ink-muted">{{ $user ? 'Signed in' : 'Not signed in' }}</p>
                        </div>
                        <div class="px-1.5 pt-1.5">
                            <x-nav.link route="profile.show" variant="sidebar" icon="user-circle">Profile</x-nav.link>
                            <x-nav.link route="settings.security" variant="sidebar" icon="cog-6-tooth">Settings</x-nav.link>
                            @if ($user && $user->can('access-admin') && Route::has('admin.dashboard'))
                                <x-nav.link route="admin.dashboard" variant="sidebar" icon="shield-check">Admin panel</x-nav.link>
                            @endif
                        </div>
                        @if ($user && Route::has('logout'))
                            <form method="POST" action="{{ route('logout') }}" class="mt-1.5 border-t border-line pt-1.5">
                                @csrf
                                <x-dropdown.item type="submit" icon="arrow-right-start-on-rectangle">Log out</x-dropdown.item>
                            </form>
                        @endif
                    </x-dropdown>
                </div>
            </div>
        </header>

        <main id="main" class="page-container flex-1 py-6 pb-24 sm:py-8 lg:pb-10">
            <x-flash class="mb-6" />
            {{ $slot }}
        </main>

        <footer class="hidden border-t border-line px-8 py-5 lg:block">
            <x-prototype-notice />
        </footer>
    </div>

    {{-- Phone bottom bar --}}
    <nav class="fixed inset-x-0 bottom-0 z-20 flex border-t border-line bg-surface pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Quick">
        @foreach (config('navigation.app_mobile') as $item)
            <x-nav.link :route="$item['route']" :icon="$item['icon']" variant="bottom">{{ $item['label'] }}</x-nav.link>
        @endforeach
    </nav>
</x-layouts.base>
