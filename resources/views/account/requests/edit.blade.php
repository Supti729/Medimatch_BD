<x-layouts.app title="Edit request">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('requests.show', $medicineRequest) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700">
            <x-icon name="arrow-left" class="size-4" /> Back to the request
        </a>
        <x-page-header class="mt-3" title="Edit request" description="You can change a request while it is still looking for a match." />
        <div class="mt-6">@include('account.requests._form')</div>
    </div>
</x-layouts.app>
