{{--
    Layout for the public website (home, browse, information pages).
    <x-layouts.public title="How it works"> ...page content... </x-layouts.public>

    The page content is full width. Wrap sections in <div class="page-container"> to align with the header.
--}}
@props(['title' => null, 'description' => null])

<x-layouts.base :title="$title" :description="$description" class="flex flex-col">
    <header class="sticky top-0 z-30 border-b border-line bg-surface/95 backdrop-blur">
        <div class="page-container flex h-16 items-center justify-between gap-4">
            <x-logo />

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
                @foreach (config('navigation.public') as $item)
                    <x-nav.link :route="$item['route']" variant="header">{{ $item['label'] }}</x-nav.link>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <div class="hidden items-center gap-2 sm:flex">
                    @auth
                        <x-nav.link route="dashboard" variant="header" icon="home">Dashboard</x-nav.link>
                    @else
                        <x-nav.link route="login" variant="header">Log in</x-nav.link>
                        @if (Route::has('register'))
                            <x-button :href="route('register')" size="sm">Create account</x-button>
                        @endif
                    @endauth
                </div>

                <button type="button" data-toggle="#public-menu" aria-expanded="false" aria-controls="public-menu"
                    class="inline-flex size-11 items-center justify-center rounded-control text-ink-soft hover:bg-brand-50 lg:hidden">
                    <span class="sr-only">Open menu</span>
                    <x-icon name="bars-3" class="size-6" />
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="public-menu" data-drawer class="hidden border-t border-line bg-surface lg:hidden">
            <nav class="page-container space-y-1 py-3" aria-label="Mobile">
                @foreach (config('navigation.public') as $item)
                    <x-nav.link :route="$item['route']" variant="drawer">{{ $item['label'] }}</x-nav.link>
                @endforeach

                <div class="mt-3 space-y-1 border-t border-line pt-3 sm:hidden">
                    @auth
                        <x-nav.link route="dashboard" variant="drawer" icon="home">Dashboard</x-nav.link>
                    @else
                        <x-nav.link route="login" variant="drawer">Log in</x-nav.link>
                        <x-nav.link route="register" variant="drawer">Create account</x-nav.link>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main id="main" class="flex-1">
        @if (session()->hasAny(['success', 'status', 'warning', 'error']))
            <div class="page-container pt-6"><x-flash /></div>
        @endif

        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-line bg-surface">
        <div class="page-container grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div>
                <x-logo />
                <p class="mt-4 max-w-xs text-sm text-ink-soft">
                    Connecting people who need eligible medicines with donors and authorized partners near them,
                    while keeping personal details private.
                </p>
            </div>

            @foreach (config('navigation.footer') as $heading => $items)
                <nav aria-label="{{ $heading }}">
                    <h2 class="text-sm font-semibold">{{ $heading }}</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($items as $item)
                            <li><x-nav.link :route="$item['route']" variant="footer">{{ $item['label'] }}</x-nav.link></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>

        <div class="border-t border-line">
            <div class="page-container flex flex-col gap-3 py-6 lg:flex-row lg:items-center lg:justify-between">
                <x-prototype-notice class="max-w-3xl" />
                <p class="shrink-0 text-xs text-ink-muted">&copy; {{ date('Y') }} Medimatch BD project team</p>
            </div>
        </div>
    </footer>
</x-layouts.base>
