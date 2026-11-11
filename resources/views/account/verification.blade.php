@php $ready = Route::has('verification.store'); @endphp

<x-layouts.app title="Verification Center" :preview="$usingSampleData">
    <x-page-header title="Verification Center" description="Some medicines need a prescription check before a handover. Upload the document here and follow its review." />

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card title="Your submissions" :padded="false">
                @if ($submissions->isEmpty())
                    <x-empty-state icon="shield-check" title="Nothing submitted" description="You only need to upload a document when one of your requests asks for a prescription check." />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($submissions as $submission)
                            <li class="p-5 sm:p-6">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base">{{ $submission['for'] }}</h3>
                                        <p class="mt-0.5 flex items-center gap-1.5 text-sm text-ink-muted"><x-icon name="document-text" class="size-4" /> {{ $submission['document'] }} · stored privately</p>
                                    </div>
                                    <x-status-badge :status="$submission['status']" />
                                </div>

                                @if ($submission['note'])
                                    <x-alert tone="warning" title="Note from the reviewer" class="mt-4">{{ $submission['note'] }}</x-alert>
                                @endif

                                <ol class="mt-4 space-y-3 border-l-2 border-line pl-4" aria-label="Status history">
                                    @foreach ($submission['history'] as [$status, $text, $at])
                                        <li class="relative text-sm">
                                            <span class="absolute top-1.5 -left-[1.4rem] size-2.5 rounded-full bg-brand-600 ring-4 ring-surface" aria-hidden="true"></span>
                                            <p class="text-ink-soft">{{ $text }}</p>
                                            <p class="text-xs text-ink-muted">{{ $at->format('j M Y, g:i a') }}</p>
                                        </li>
                                    @endforeach
                                </ol>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="Upload a document">
                <x-not-connected route="verification.store" feature="Uploading a document" class="mb-5" />

                <form class="space-y-5" enctype="multipart/form-data" @if ($ready) method="POST" action="{{ route('verification.store') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
                    @csrf
                    <x-form.select name="request_id" label="Which request is this for?" required placeholder="Select a request"
                        :options="$requests->mapWithKeys(fn ($r) => [$r['id'] => $r['name'].' '.$r['strength'].' · '.$r['quantity'].' '.$r['unit']])->all()"
                        hint="Only your open requests that need a prescription check are listed." />

                    <div>
                        @php $errors ??= new \Illuminate\Support\ViewErrorBag; @endphp
                        <label for="document" class="block text-sm font-medium">Prescription <span class="text-danger-600" aria-hidden="true">*</span></label>
                        <input type="file" name="document" id="document" required accept="image/jpeg,image/png,application/pdf" aria-describedby="document-hint"
                            class="mt-1.5 block w-full rounded-control border border-line bg-surface text-sm text-ink-soft file:mr-4 file:min-h-11 file:cursor-pointer file:border-0 file:bg-brand-50 file:px-4 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100">
                        @error('document')
                            <p class="mt-1.5 text-sm text-danger-700">{{ $message }}</p>
                        @else
                            <p id="document-hint" class="mt-1.5 text-sm text-ink-muted">JPG, PNG or PDF, up to 5 MB. The doctor's name, date and medicine must be readable.</p>
                        @enderror
                    </div>

                    <x-form.checkbox name="consent" required label="I agree that an authorized reviewer may view this document to check my request." />

                    <x-button type="submit" icon="arrow-up-tray" :disabled="! $ready">Upload for review</x-button>
                </form>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="How your document is handled">
                <ul class="space-y-3 text-sm text-ink-soft">
                    @foreach ([
                        ['lock-closed', 'Kept in private storage. It has no public link.'],
                        ['eye', 'Seen only by authorized reviewers, and every view is recorded.'],
                        ['eye-slash', 'Never shown to donors. They only learn whether the check passed.'],
                        ['user', 'Reviewed by a person. AI does not approve or reject prescriptions.'],
                    ] as [$icon, $text])
                        <li class="flex items-start gap-2.5"><x-icon :name="$icon" class="mt-0.5 size-5 text-brand-600" /> <span>{{ $text }}</span></li>
                    @endforeach
                </ul>
            </x-card>

            <x-alert tone="warning" title="Prototype">
                Do not upload a real prescription. This academic prototype is not authorized to process medical documents.
            </x-alert>

            <x-card title="What the statuses mean">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'submitted' => 'Uploaded and waiting for a reviewer.',
                        'under_review' => 'A reviewer is checking it.',
                        'verified' => 'Accepted. The handover can go ahead.',
                        'reupload_required' => 'A clearer or newer document is needed.',
                        'rejected' => 'Not accepted. The reason is shown.',
                    ] as $status => $meaning)
                        <div class="flex items-start gap-3">
                            <dt class="w-36 shrink-0"><x-status-badge :status="$status" /></dt>
                            <dd class="text-ink-soft">{{ $meaning }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        </aside>
    </div>
</x-layouts.app>
