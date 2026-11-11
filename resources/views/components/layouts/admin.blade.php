{{--
    Layout for the administration panel: dark sidebar, plain top bar.
    <x-layouts.admin title="Users"> ...page content... </x-layouts.admin>

    This layout only controls appearance. Access is enforced by route middleware and policies.
--}}
@props(['title' => null])

@php
    $user = auth()->user();
    $displayName = $user?->name ?? 'Guest';
    $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
@endphp

<x-layouts.base :title="$title ? $title.' · Admin' : 'Admin'" noindex>
    {{-- Desktop sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-brand-950 lg:flex">
        <div class="flex h-16 shrink-0 items-center border-b border-white/10 px-5">
            <x-logo tone="light" />
        </div>
        <div class="flex-1 overflow-y-auto px-3 py-5 [scrollbar-color:rgb(255_255_255/0.25)_transparent] [scrollbar-width:thin]">
            <x-nav.menu :groups="config('navigation.admin')" variant="admin" aria-label="Admin" />
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div id="admin-drawer" data-drawer class="fixed inset-0 z-50 hidden lg:hidden">
        <button type="button" data-toggle="#admin-drawer" class="absolute inset-0 size-full cursor-default bg-ink/60" aria-label="Close menu" tabindex="-1"></button>
        <div class="relative flex h-full w-72 max-w-[85%] flex-col bg-brand-950 shadow-overlay">
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-white/10 px-4">
                <x-logo tone="light" />
                <button type="button" data-toggle="#admin-drawer" class="inline-flex size-11 items-center justify-center rounded-control text-brand-100 hover:bg-white/10">
                    <span class="sr-only">Close menu</span>
                    <x-icon name="x-mark" class="size-6" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-3 py-5 [scrollbar-color:rgb(255_255_255/0.25)_transparent] [scrollbar-width:thin]">
                <x-nav.menu :groups="config('navigation.admin')" variant="admin" aria-label="Admin (mobile)" />
            </div>
        </div>
    </div>

    <div class="flex min-h-screen flex-col lg:pl-64">
        <header class="sticky top-0 z-20 border-b border-line bg-surface/95 backdrop-blur">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button type="button" data-toggle="#admin-drawer" aria-expanded="false" aria-controls="admin-drawer"
                    class="-ml-2 inline-flex size-11 items-center justify-center rounded-control text-ink-soft hover:bg-brand-50 lg:hidden">
                    <span class="sr-only">Open menu</span>
                    <x-icon name="bars-3" class="size-6" />
                </button>

                <x-badge tone="brand" class="shrink-0">Admin</x-badge>
                <p class="truncate text-sm font-semibold lg:text-base">{{ $title ?? 'Administration' }}</p>

                <div class="ml-auto flex items-center gap-2">
                    <x-button :href="Route::has('home') ? route('home') : url('/')" variant="ghost" size="sm" icon="arrow-top-right-on-square" class="hidden sm:inline-flex">
                        View site
                    </x-button>

                    <x-dropdown>
                        <x-slot:trigger>
                            <button type="button" data-dropdown-trigger aria-haspopup="true" aria-expanded="false"
                                class="inline-flex min-h-11 items-center gap-2 rounded-control py-1 pr-2 pl-1 hover:bg-brand-50">
                                <span class="flex size-9 items-center justify-center rounded-full bg-brand-900 text-sm font-semibold text-white" aria-hidden="true">{{ $initial }}</span>
                                <span class="hidden max-w-32 truncate text-sm font-medium sm:block">{{ $displayName }}</span>
                                <x-icon name="chevron-down" class="hidden size-4 text-ink-muted sm:block" />
                                <span class="sr-only">Open account menu</span>
                            </button>
                        </x-slot:trigger>

                        <div class="border-b border-line px-4 pt-1 pb-3">
                            <p class="truncate text-sm font-semibold">{{ $displayName }}</p>
                            <p class="text-xs text-ink-muted">Administrator area</p>
                        </div>
                        <div class="px-1.5 pt-1.5">
                            <x-nav.link route="dashboard" variant="sidebar" icon="home">My dashboard</x-nav.link>
                            <x-nav.link route="admin.settings" variant="sidebar" icon="cog-6-tooth">Admin settings</x-nav.link>
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

        <main id="main" class="page-container flex-1 py-6 sm:py-8">
            <x-flash class="mb-6" />
            {{ $slot }}
        </main>
    </div>
</x-layouts.base>
