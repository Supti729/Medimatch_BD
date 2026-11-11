<x-layouts.app title="User layout preview">
    <div class="space-y-6">
        @include('dev._nav')
        <x-page-header title="Signed-in user layout" description="Sidebar on desktop; menu drawer and bottom bar on phones. Menu items marked “Soon” link to pages that are not built yet.">
            <x-button icon="plus" disabled>New listing</x-button>
        </x-page-header>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Active listings" icon="gift" hint="No data in the layout preview" />
            <x-stat-card label="Active requests" icon="clipboard-document-list" tone="info" hint="No data in the layout preview" />
            <x-stat-card label="Reserved" icon="clock" tone="warning" hint="No data in the layout preview" />
            <x-stat-card label="Completed" icon="check-circle" tone="success" hint="No data in the layout preview" />
        </div>
        <x-card title="Content area" :padded="false">
            <x-empty-state title="Nothing here yet" description="Real pages are added in the next front-end steps." />
        </x-card>
    </div>
</x-layouts.app>
