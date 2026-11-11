<x-layouts.public title="Public layout preview">
    <div class="page-container space-y-6 py-8">
        @include('dev._nav')
        <x-page-header title="Public layout" description="Header, mobile menu and footer used by the home page and information pages. Menu items marked “Soon” link to pages that are not built yet." />
        <x-card>
            <p class="text-sm text-ink-soft">Page content goes here. Resize the window to under 1024px to see the mobile menu button.</p>
        </x-card>
    </div>
</x-layouts.public>
