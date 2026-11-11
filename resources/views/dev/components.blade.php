{{-- Component gallery. Local development only. Every example here is sample content. --}}
<x-layouts.base title="Components" noindex>
    <main id="main" class="page-container space-y-12 py-10">
        @include('dev._nav')

        <x-page-header title="Components" description="Reusable Blade components in resources/views/components. All content on this page is sample data.">
            <x-button variant="secondary" icon="arrow-left" :href="route('styleguide')">Tokens</x-button>
        </x-page-header>

        <section aria-labelledby="c-buttons">
            <h2 id="c-buttons" class="text-xl">Buttons</h2>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-button>Primary</x-button>
                <x-button variant="secondary">Secondary</x-button>
                <x-button variant="accent" icon="gift">Donate medicine</x-button>
                <x-button variant="ghost">Ghost</x-button>
                <x-button variant="danger" icon="trash">Danger</x-button>
                <x-button disabled>Disabled</x-button>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <x-button size="sm">Small</x-button>
                <x-button size="md">Medium</x-button>
                <x-button size="lg" icon-right="arrow-right">Large</x-button>
                <x-button href="#c-buttons" variant="secondary" icon="magnifying-glass">Link styled as button</x-button>
            </div>
        </section>

        <section aria-labelledby="c-badges">
            <h2 id="c-badges" class="text-xl">Badges and statuses</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach (['neutral', 'brand', 'accent', 'success', 'warning', 'danger', 'info'] as $tone)
                    <x-badge :tone="$tone">{{ ucfirst($tone) }}</x-badge>
                @endforeach
            </div>
            @foreach ([
                'Listing' => ['draft', 'pending_review', 'available', 'requested', 'reserved', 'completed', 'cancelled', 'rejected', 'blocked'],
                'Request' => ['active', 'matching', 'match_found', 'verification_pending', 'reserved', 'completed', 'cancelled', 'expired'],
                'Verification' => ['not_submitted', 'submitted', 'under_review', 'verified', 'rejected', 'reupload_required'],
            ] as $group => $statuses)
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="w-24 text-sm font-medium text-ink-muted">{{ $group }}</span>
                    @foreach ($statuses as $status)
                        <x-status-badge :status="$status" />
                    @endforeach
                </div>
            @endforeach
        </section>

        <section aria-labelledby="c-alerts">
            <h2 id="c-alerts" class="text-xl">Alerts</h2>
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                <x-alert tone="info" title="Heads up">Listings are reviewed before they become visible.</x-alert>
                <x-alert tone="success" title="Saved" dismissible>Your changes were saved. (Dismissible)</x-alert>
                <x-alert tone="warning" title="Expiring soon">This medicine expires within 60 days.</x-alert>
                <x-alert tone="danger" title="Could not save">Please fix the highlighted fields.</x-alert>
            </div>
        </section>

        <section aria-labelledby="c-forms">
            <h2 id="c-forms" class="text-xl">Form fields</h2>
            <x-card class="mt-4">
                {{-- Demo only: the submit event is cancelled after 1.5 s so you can see the loading state --}}
                <form data-loading id="demo-form" class="grid gap-5 sm:grid-cols-2" onsubmit="event.preventDefault(); setTimeout(() => location.reload(), 1500)">
                    <x-form.input name="medicine_name" label="Medicine name" placeholder="e.g. Napa 500 mg" icon="magnifying-glass" required />
                    <x-form.input name="quantity" label="Quantity" type="number" min="1" hint="Number of tablets, capsules or bottles." required />
                    <x-form.select name="division" label="Division" placeholder="Select division" :options="['dhaka' => 'Dhaka', 'chattogram' => 'Chattogram', 'khulna' => 'Khulna']" required />
                    <x-form.input name="expiry" label="Expiry date" type="month" optional />
                    <x-form.textarea name="notes" label="Additional information" placeholder="Do not include personal or medical details." optional class="sm:col-span-2" />
                    <div class="sm:col-span-2">
                        <x-form.checkbox name="confirm" label="I confirm this medicine is unopened and not expired." hint="Listings that break the safety guidelines are removed." />
                    </div>
                    <div class="flex gap-2 sm:col-span-2">
                        <x-button type="submit">Submit (shows loading)</x-button>
                        <x-button type="reset" variant="secondary">Reset</x-button>
                    </div>
                </form>
            </x-card>
            <p class="mt-2 text-sm text-ink-muted">Validation errors appear under each field automatically once the backend returns them.</p>
        </section>

        <section aria-labelledby="c-cards">
            <h2 id="c-cards" class="text-xl">Cards and statistics</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card label="Active listings" value="3" icon="gift" hint="Sample value" />
                <x-stat-card label="Active requests" value="1" icon="clipboard-document-list" tone="info" hint="Sample value" />
                <x-stat-card label="Pending verification" value="2" icon="shield-check" tone="warning" hint="Sample value" />
                <x-stat-card label="Not loaded yet" icon="clock" tone="accent" hint="Shows a dash when the value is null" />
            </div>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <x-card title="Card with header" description="Title, description, actions and footer slots.">
                    <x-slot:actions><x-button size="sm" variant="secondary">View all</x-button></x-slot:actions>
                    <p class="text-sm text-ink-soft">Card body content goes here.</p>
                    <x-slot:footer><p class="text-sm text-ink-muted">Footer slot</p></x-slot:footer>
                </x-card>
                <x-card title="Loading state">
                    <x-skeleton :lines="4" />
                    <p class="mt-4 flex items-center gap-2 text-sm text-ink-muted"><x-spinner class="size-4 text-brand-600" /> Spinner</p>
                </x-card>
            </div>
        </section>

        <section aria-labelledby="c-states">
            <h2 id="c-states" class="text-xl">Empty and error states</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <x-card :padded="false">
                    <x-empty-state icon="gift" title="No listings yet" description="Offer a medicine you no longer need and help someone nearby.">
                        <x-button icon="plus">Create listing</x-button>
                    </x-empty-state>
                </x-card>
                <x-card :padded="false">
                    <x-error-state title="Search is unavailable" description="We could not load results. Please try again in a moment.">
                        <x-button variant="secondary" :href="url()->current()">Try again</x-button>
                    </x-error-state>
                </x-card>
            </div>
        </section>

        <section aria-labelledby="c-overlays">
            <h2 id="c-overlays" class="text-xl">Dropdown and modals</h2>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-dropdown align="left">
                    <x-slot:trigger>
                        <x-button variant="secondary" icon-right="chevron-down" data-dropdown-trigger aria-haspopup="true" aria-expanded="false">Dropdown</x-button>
                    </x-slot:trigger>
                    <x-dropdown.item href="#c-overlays" icon="user-circle">Link item</x-dropdown.item>
                    <x-dropdown.item icon="flag">Button item</x-dropdown.item>
                </x-dropdown>

                <x-button variant="secondary" data-modal-open="demo-modal">Open modal</x-button>
                <x-button variant="danger" data-modal-open="demo-confirm">Open confirmation</x-button>
            </div>

            <x-modal id="demo-modal" title="Report this listing">
                <p>Modals use the browser's dialog element. Press Escape, click outside, or use the buttons to close.</p>
                <x-slot:footer>
                    <x-button variant="secondary" data-modal-close>Cancel</x-button>
                    <x-button data-modal-close>Done</x-button>
                </x-slot:footer>
            </x-modal>

            {{-- Demo only: posts back to this page, which has no POST route, so "Go back" is the safe choice here --}}
            <x-confirm-modal id="demo-confirm" title="Cancel this listing?" :action="url()->current()" confirm="Yes, cancel listing" tone="danger">
                People will no longer be able to request this medicine. (Demo: confirming has no real effect.)
            </x-confirm-modal>
        </section>
    </main>
</x-layouts.base>
