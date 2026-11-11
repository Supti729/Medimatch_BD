<x-layouts.admin title="Admin layout preview">
    <div class="space-y-6">
        @include('dev._nav')
        <x-page-header title="Admin layout" description="Dark sidebar and top bar for the administration panel. This preview has no access control; real admin pages are protected in the backend part." />
        <x-card title="Content area" :padded="false">
            <x-empty-state icon="chart-bar" title="Nothing here yet" description="Admin pages are added in push F4." />
        </x-card>
    </div>
</x-layouts.admin>
