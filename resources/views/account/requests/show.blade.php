@php
    $open = in_array($request['status'], ['active', 'matching', 'match_found', 'verification_pending', 'reserved'], true);
    $currentStep = array_search($request['status'], $steps, true);
    $stepLabels = ['active' => 'Posted', 'matching' => 'Matching', 'match_found' => 'Match found', 'verification_pending' => 'Verification', 'reserved' => 'Reserved', 'completed' => 'Completed'];
    $preferences = ['either' => 'Donation or affordable access', 'donation' => 'Donation only', 'affordable_access' => 'Affordable access'];
    $connected = Route::has('requests.cancel');
@endphp

<x-layouts.app :title="'Request: '.$request['name']" :preview="$usingSampleData">
    <a href="{{ route('requests.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700">
        <x-icon name="arrow-left" class="size-4" /> My requests
    </a>

    <x-page-header class="mt-3" :title="$request['name'].' '.$request['strength']" :description="$request['generic_name'].' · '.$request['dosage_form']">
        <x-status-badge :status="$request['status']" />
        @if ($open)
            <x-button variant="secondary" size="sm" data-modal-open="cancel-request">Cancel request</x-button>
        @endif
    </x-page-header>

    {{-- Progress --}}
    @if ($currentStep !== false)
        <x-card class="mt-6">
            <ol class="grid gap-4 sm:grid-cols-6" aria-label="Request progress">
                @foreach ($steps as $i => $step)
                    @php $done = $i < $currentStep; $now = $i === $currentStep; @endphp
                    <li class="flex items-center gap-3 sm:flex-col sm:items-start sm:gap-2" @if ($now) aria-current="step" @endif>
                        <span @class([
                            'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                            'bg-brand-600 text-white' => $done || $now,
                            'bg-canvas text-ink-muted ring-1 ring-line ring-inset' => ! $done && ! $now,
                        ])>
                            @if ($done) <x-icon name="check" class="size-4" /> @else {{ $i + 1 }} @endif
                        </span>
                        <span @class(['text-sm', 'font-semibold text-ink' => $now, 'text-ink-soft' => $done, 'text-ink-muted' => ! $done && ! $now])>
                            {{ $stepLabels[$step] }}
                            @if ($now) <span class="sr-only">(current step)</span> @endif
                        </span>
                    </li>
                @endforeach
            </ol>
        </x-card>
    @else
        <x-alert tone="info" class="mt-6">This request is {{ str_replace('_', ' ', $request['status']) }} and is no longer being matched.</x-alert>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            @if ($request['status'] === 'verification_pending')
                <x-alert tone="warning" title="A prescription check is needed">
                    This medicine needs a prescription. Upload it in the Verification Center. Only an authorized reviewer sees it, never the donor.
                    <a href="{{ route('verification.index') }}" class="mt-2 block font-semibold text-warning-700 underline">Go to the Verification Center</a>
                </x-alert>
            @endif

            <x-card title="Potential matches" :description="$open ? 'Available listings with the same medicine, strength and dosage form. Your district is shown first.' : null" :padded="false">
                @if ($matches->isEmpty())
                    <x-empty-state icon="sparkles" :title="$open ? 'No matching listing yet' : 'Matching has stopped'"
                        :description="$open ? 'We will notify you as soon as a suitable medicine is listed.' : 'Closed requests are not matched.'">
                        @if ($open)
                            <x-button :href="route('medicines.index', ['q' => $request['generic_name']])" variant="secondary" size="sm">Search listings yourself</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <div class="grid gap-4 p-5 sm:p-6">
                        @foreach ($matches as $listing)
                            <x-medicine-card :listing="$listing" layout="list" class="shadow-none" />
                        @endforeach
                    </div>
                @endif
                <x-slot:footer>
                    <p class="flex items-start gap-2 text-xs text-ink-muted">
                        <x-icon name="information-circle" class="size-4" />
                        <span>A match means the listing text fits your request. It is not advice that the medicine is suitable for you. AI ranking is added in a later part of the project; this list uses fixed rules.</span>
                    </p>
                </x-slot:footer>
            </x-card>
        </div>

        <aside>
            <x-card title="Request details">
                <x-detail-list :columns="1" :items="[
                    'Medicine' => $request['name'],
                    'Generic name' => $request['generic_name'],
                    'Strength' => $request['strength'],
                    'Dosage form' => $request['dosage_form'],
                    'Quantity needed' => $request['quantity'].' '.$request['unit'],
                    'Area' => $request['area'].', '.$request['district'],
                    'Assistance' => $preferences[$request['preference']] ?? $request['preference'],
                    'Additional information' => $request['notes'] ?? 'None',
                    'Requested on' => $request['created_at']->format('j M Y'),
                ]" />
            </x-card>
        </aside>
    </div>

    @if ($open)
        @if ($connected)
            <x-confirm-modal id="cancel-request" title="Cancel this request?" :action="route('requests.cancel', $request['id'])" method="PATCH" confirm="Yes, cancel request" tone="danger">
                We will stop looking for matches, and any reservation will be released.
            </x-confirm-modal>
        @else
            <x-modal id="cancel-request" title="Cancel this request?" size="sm">
                <p>We will stop looking for matches, and any reservation will be released.</p>
                <x-not-connected route="requests.cancel" feature="Cancelling a request" class="mt-4" />
                <x-slot:footer>
                    <x-button variant="secondary" data-modal-close>Go back</x-button>
                    <x-button variant="danger" disabled>Yes, cancel request</x-button>
                </x-slot:footer>
            </x-modal>
        @endif
    @endif
</x-layouts.app>
