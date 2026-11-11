<x-layouts.app title="Saved medicines" :preview="$usingSampleData">
    <x-page-header title="Saved medicines" description="Listings you bookmarked to look at again.">
        <x-button :href="route('medicines.index')" variant="secondary" icon="magnifying-glass">Browse medicines</x-button>
    </x-page-header>

    @if ($listings->isEmpty())
        <x-card class="mt-6" :padded="false">
            <x-empty-state icon="bookmark" title="No saved medicines" description="Use the Save button on a listing to keep it here.">
                <x-button :href="route('medicines.index')">Browse medicines</x-button>
            </x-empty-state>
        </x-card>
    @else
        <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($listings as $listing)
                <div class="flex flex-col">
                    <x-medicine-card :listing="$listing" class="flex-1 rounded-b-none" />
                    <div class="flex items-center justify-between gap-2 rounded-b-card border border-t-0 border-line bg-canvas/60 px-5 py-2.5">
                        <span class="text-xs text-ink-muted">{{ $listing['status'] === 'available' ? 'Still available' : 'No longer available to request' }}</span>
                        <x-button variant="ghost" size="sm" icon="bookmark-slash" :disabled="! Route::has('saved.destroy')" title="{{ Route::has('saved.destroy') ? '' : 'Not connected yet' }}">Remove</x-button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
