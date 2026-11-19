<x-layouts.app title="Saved medicines">
    <x-page-header title="Saved medicines" description="Listings you bookmarked to look at again.">
        <x-button :href="route('medicines.index')" variant="secondary" icon="magnifying-glass">Browse medicines</x-button>
    </x-page-header>

    @if ($goneIds)
        <x-alert tone="info" class="mt-6" title="{{ count($goneIds) }} saved {{ Str::plural('listing', count($goneIds)) }} no longer available">
            They were completed, cancelled or removed by the donor.
            @foreach ($goneIds as $goneId)
                <form method="POST" action="{{ route('saved.destroy', $goneId) }}" class="inline">@csrf @method('DELETE')
                    <button type="submit" class="font-semibold underline">Remove{{ count($goneIds) > 1 ? ' #'.($loop->iteration) : '' }}</button>
                </form>
            @endforeach
        </x-alert>
    @endif

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
                        <span class="text-xs text-ink-muted">{{ in_array($listing['status'], ['available', 'requested'], true) ? 'Can be requested' : 'Reserved for someone else' }}</span>
                        <form method="POST" action="{{ route('saved.destroy', $listing['id']) }}" data-loading>
                            @csrf @method('DELETE')
                            <x-button type="submit" variant="ghost" size="sm" icon="bookmark-slash">Remove</x-button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
