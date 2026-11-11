{{--
    Layout for login, registration and password pages: a centred card under the logo.
    <x-layouts.auth title="Log in" heading="Welcome back" subheading="Log in to continue.">
        ...form...
        <x-slot:footer>New here? <a href="...">Create an account</a></x-slot:footer>
    </x-layouts.auth>

    wide: use for longer forms such as registration.
--}}
@props(['title' => null, 'heading' => null, 'subheading' => null, 'wide' => false])

<x-layouts.base :title="$title" noindex class="flex flex-col">
    <main id="main" class="flex flex-1 flex-col items-center px-4 py-10 sm:py-16">
        <x-logo />

        <div @class(['mt-8 w-full', 'max-w-md' => ! $wide, 'max-w-2xl' => $wide])>
            <div class="rounded-card border border-line bg-surface p-6 shadow-card sm:p-8">
                @if ($heading)
                    <h1 class="text-2xl font-semibold tracking-tight">{{ $heading }}</h1>
                @endif
                @if ($subheading)
                    <p class="mt-1.5 text-sm text-ink-soft">{{ $subheading }}</p>
                @endif

                <x-flash class="mt-5" />

                <div @class(['mt-6' => $heading || $subheading])>{{ $slot }}</div>
            </div>

            @isset($footer)
                <p class="mt-6 text-center text-sm text-ink-soft">{{ $footer }}</p>
            @endisset
        </div>
    </main>

    <footer class="px-4 pb-8">
        <x-prototype-notice class="mx-auto max-w-2xl text-center" />
    </footer>
</x-layouts.base>
