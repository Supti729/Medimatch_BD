<x-layouts.app title="Verification Center">
    <x-page-header title="Verification Center" description="Prescription medicines need a prescription check before a handover. Upload the document here and follow its review." />

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card title="Upload a prescription">
                @if ($requests->isEmpty())
                    <x-empty-state icon="shield-check" title="Nothing needs a prescription right now" class="py-6"
                        description="You only need to upload a document for an open request of a prescription medicine.">
                        <x-button :href="route('requests.create')" variant="secondary" size="sm">Request a medicine</x-button>
                    </x-empty-state>
                @else
                    <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" class="space-y-5" data-loading>
                        @csrf
                        <x-form.select name="medicine_request_id" label="Which request is this for?" required placeholder="Select a request"
                            :options="$requests->mapWithKeys(fn ($r) => [$r->id => $r->medicine->label().' · '.$r->quantity.' '.$r->unit])->all()" />

                        <div>
                            <label for="document" class="block text-sm font-medium">Prescription <span class="text-danger-600" aria-hidden="true">*</span></label>
                            <input type="file" name="document" id="document" required accept="image/jpeg,image/png,application/pdf" aria-describedby="document-hint"
                                @error('document') aria-invalid="true" @enderror
                                class="mt-1.5 block w-full rounded-control border border-line bg-surface text-sm text-ink-soft file:mr-4 file:min-h-11 file:cursor-pointer file:border-0 file:bg-brand-50 file:px-4 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100">
                            @error('document')
                                <p class="mt-1.5 text-sm text-danger-700">{{ $message }}</p>
                            @else
                                <p id="document-hint" class="mt-1.5 text-sm text-ink-muted">JPG, PNG or PDF, up to 5 MB. The doctor's name, date and medicine must be readable.</p>
                            @enderror
                        </div>

                        <x-form.checkbox name="consent" required label="I agree that an authorized reviewer may view this document to check my request." />

                        <x-button type="submit" icon="arrow-up-tray">Upload for review</x-button>
                    </form>
                @endif
            </x-card>

            <x-card title="Your submissions" :padded="false">
                @if ($submissions->isEmpty())
                    <x-empty-state icon="document-text" title="Nothing submitted yet" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($submissions as $submission)
                            <li class="p-5 sm:p-6">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base">{{ $submission->request->medicine->label() }}</h3>
                                        <p class="mt-0.5 flex items-center gap-1.5 text-sm text-ink-muted"><x-icon name="document-text" class="size-4" /> Prescription · stored privately · {{ $submission->created_at->format('j M Y') }}</p>
                                    </div>
                                    <x-status-badge :status="$submission->status" />
                                </div>

                                @if ($submission->reviewer_note)
                                    <x-alert :tone="$submission->status->value === 'verified' ? 'success' : 'warning'" title="Note from the reviewer" class="mt-4">{{ $submission->reviewer_note }}</x-alert>
                                @endif

                                <ol class="mt-4 space-y-3 border-l-2 border-line pl-4" aria-label="Status history">
                                    @foreach ($submission->events as $event)
                                        <li class="relative text-sm">
                                            <span class="absolute top-1.5 -left-[1.4rem] size-2.5 rounded-full bg-brand-600 ring-4 ring-surface" aria-hidden="true"></span>
                                            <p class="text-ink-soft">{{ [
                                                'submitted' => 'You uploaded a document.',
                                                'under_review' => 'An authorized reviewer started the review.',
                                                'verified' => 'The prescription was accepted.',
                                                'reupload_required' => 'The reviewer asked for a new document.',
                                                'rejected' => 'The prescription was not accepted.',
                                            ][$event->status] ?? $event->status }}</p>
                                            <p class="text-xs text-ink-muted">{{ $event->created_at->format('j M Y, g:i a') }}</p>
                                        </li>
                                    @endforeach
                                </ol>
                            </li>
                        @endforeach
                    </ul>
                @endif
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

            <x-alert tone="warning" title="Academic prototype">
                Do not upload a real prescription. This prototype is not authorized to process real medical documents.
            </x-alert>
        </aside>
    </div>
</x-layouts.app>
