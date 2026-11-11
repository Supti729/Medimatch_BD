@php
    $hasSearch = $filters['q'] !== '' || $activeFilterCount > 0;
    $title = $filters['q'] !== '' ? 'Results for “'.$filters['q'].'”' : 'Browse medicines';

    // Links that keep the current filters but change one thing
    $withQuery = fn (array $change) => route('medicines.index', array_filter(array_merge(request()->except('page'), $change), fn ($v) => $v !== null && $v !== ''));
@endphp

<x-layouts.public :title="$title">
    <div class="border-b border-line bg-surface">
        <div class="page-container py-8">
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $title }}</h1>
            <p class="mt-1.5 text-ink-soft">Search eligible medicines offered by donors and partners across Bangladesh.</p>
        </div>
    </div>

    <div class="page-container py-8">
        @if ($usingSampleData)
            <x-sample-notice class="mb-6">
                These listings are invented examples for the prototype, not real offers of medicine. Search, filters and sorting work on them.
            </x-sample-notice>
        @endif

        <form method="GET" action="{{ route('medicines.index') }}" role="search" class="grid gap-6 lg:grid-cols-[17rem_1fr]">
            @if ($view === 'list')
                <input type="hidden" name="view" value="list">
            @endif

            {{-- Filters: always visible on desktop, collapsible on phones --}}
            <aside aria-label="Filters">
                <button type="button" data-toggle="#filter-panel" aria-expanded="false" aria-controls="filter-panel"
                    class="flex min-h-11 w-full items-center justify-between rounded-control border border-line bg-surface px-4 text-sm font-semibold shadow-card lg:hidden">
                    <span class="flex items-center gap-2">
                        <x-icon name="adjustments-horizontal" class="size-5" /> Filters
                        @if ($activeFilterCount)
                            <x-badge tone="brand">{{ $activeFilterCount }}</x-badge>
                        @endif
                    </span>
                    <x-icon name="chevron-down" class="size-4" />
                </button>

                <div id="filter-panel" class="mt-3 hidden space-y-5 rounded-card border border-line bg-surface p-5 shadow-card lg:mt-0 lg:block">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold">Filters</h2>
                        @if ($activeFilterCount)
                            <a href="{{ route('medicines.index', array_filter(['q' => $filters['q'], 'view' => $view === 'list' ? 'list' : null])) }}" class="text-sm font-medium text-brand-700 hover:underline">Clear all</a>
                        @endif
                    </div>

                    <x-form.location :division="$filters['division']" :district="$filters['district']" id-prefix="filter-" />

                    <fieldset>
                        <legend class="text-sm font-medium">Listing type</legend>
                        <div class="mt-2 space-y-2">
                            @foreach (['' => 'Any type'] + config('medimatch.listing_types') as $value => $label)
                                <label class="flex items-center gap-2.5 text-sm text-ink-soft">
                                    <input type="radio" name="type" value="{{ $value }}" @checked($filters['type'] === $value) class="size-4 accent-brand-600">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <x-form.select name="form" id="filter-form" label="Dosage form" placeholder="Any form" :selected="$filters['form']"
                        :options="array_combine(config('medimatch.dosage_forms'), config('medimatch.dosage_forms'))" />

                    <x-form.input name="strength" id="filter-strength" label="Strength" :value="$filters['strength']" placeholder="e.g. 500 mg" maxlength="30" />

                    <x-form.checkbox name="available" id="filter-available" label="Available now only" hint="Hide requested and reserved listings." :checked="$filters['available']" />

                    <x-button type="submit" block>Apply filters</x-button>
                </div>
            </aside>

            <div class="min-w-0">
                {{-- Search bar --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <x-form.input name="q" id="search-q" aria-label="Medicine, generic or brand name" :value="$filters['q']"
                            type="search" placeholder="Medicine, generic or brand name" icon="magnifying-glass" maxlength="80" />
                    </div>
                    <x-button type="submit">Search</x-button>
                </div>

                {{-- Result count, sort and view switch --}}
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-ink-soft" role="status">
                        <span class="font-semibold text-ink">{{ $listings->total() }}</span>
                        {{ Str::plural('listing', $listings->total()) }} found
                    </p>

                    <div class="flex items-center gap-2">
                        <label for="sort" class="sr-only">Sort by</label>
                        <div class="relative">
                            <select name="sort" id="sort" data-autosubmit
                                class="min-h-10 appearance-none rounded-control border border-line bg-surface py-2 pr-9 pl-3 text-sm focus:border-brand-600 focus:outline-2 focus:outline-offset-0 focus:outline-brand-600/30">
                                @foreach (config('medimatch.sort_options') as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-ink-muted"><x-icon name="chevron-down" class="size-4" /></span>
                        </div>

                        <div class="flex rounded-control border border-line bg-surface p-0.5" role="group" aria-label="Layout">
                            @foreach (['grid' => ['squares-2x2', 'Grid view'], 'list' => ['bars-3', 'List view']] as $mode => [$icon, $label])
                                <a href="{{ $withQuery(['view' => $mode === 'list' ? 'list' : null]) }}" @if ($view === $mode) aria-current="true" @endif
                                    @class(['inline-flex size-9 items-center justify-center rounded-[0.5rem]', 'bg-brand-600 text-white' => $view === $mode, 'text-ink-muted hover:bg-brand-50' => $view !== $mode])>
                                    <span class="sr-only">{{ $label }}</span>
                                    <x-icon :name="$icon" class="size-5" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Results --}}
                @if ($listings->isEmpty())
                    <x-card class="mt-5" :padded="false">
                        <x-empty-state icon="magnifying-glass"
                            :title="$hasSearch ? 'No listings match your search' : 'No listings yet'"
                            :description="$hasSearch ? 'Try a different spelling, the generic name, or fewer filters. You can also post a request and be notified when a match is listed.' : 'Medicines offered by donors will appear here.'">
                            @if ($hasSearch)
                                <x-button :href="route('medicines.index')" variant="secondary">Clear search</x-button>
                            @endif
                            <x-button :href="Route::has('requests.create') ? route('requests.create') : route('register')">Request this medicine</x-button>
                        </x-empty-state>
                    </x-card>
                @else
                    <div @class(['mt-5 grid gap-5', 'sm:grid-cols-2 xl:grid-cols-3' => $view === 'grid'])>
                        @foreach ($listings as $listing)
                            <x-medicine-card :listing="$listing" :layout="$view" />
                        @endforeach
                    </div>

                    <div class="mt-8">{{ $listings->links('partials.pagination') }}</div>
                @endif
            </div>
        </form>
    </div>
</x-layouts.public>
