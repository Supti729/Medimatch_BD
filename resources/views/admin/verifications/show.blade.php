@php
    $s = $submission;
    $isImage = str_starts_with($s->mime_type, 'image/');
@endphp

<x-layouts.admin :title="'Verification #'.$s->id">
    <a href="{{ route('admin.verifications.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" /> Verification</a>

    <x-page-header class="mt-3" :title="'Document review #'.$s->id" :description="$s->request->medicine->label().' · '.$s->request->quantity.' '.$s->request->unit">
        <x-status-badge :status="$s->status" />
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card title="Document" description="Served from private storage, only to reviewers. Opening it is recorded in the audit log.">
                <div class="flex flex-col items-center justify-center rounded-control border border-dashed border-line bg-canvas p-6 text-center">
                    <x-icon :name="$isImage ? 'photo' : 'document-text'" class="size-10 text-ink-muted" />
                    <p class="mt-3 text-sm font-semibold">{{ $isImage ? 'Image' : 'PDF document' }} · {{ $s->size_kb }} KB</p>
                    <x-button :href="route('admin.verifications.document', $s)" class="mt-4" icon="eye" target="_blank" rel="noopener">Open document</x-button>
                    <p class="mt-3 max-w-sm text-xs text-ink-muted">Opens in a new tab. Do not save or share it.</p>
                </div>
            </x-card>

            <x-card title="What to check">
                <ul class="space-y-2.5 text-sm text-ink-soft">
                    @foreach ([
                        'The prescription is from a registered doctor, with a readable name and registration number.',
                        'The date is recent enough for the medicine.',
                        'The medicine, strength and form match the request: '.$s->request->medicine->label().'.',
                        'The quantity requested ('.$s->request->quantity.' '.$s->request->unit.') is not more than prescribed.',
                        'The document does not look altered.',
                    ] as $point)
                        <li class="flex items-start gap-2.5"><x-icon name="check" class="mt-0.5 size-5 text-brand-600" /> {{ $point }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-card title="Status history" :padded="false">
                <ol class="divide-y divide-line">
                    @foreach ($s->events as $event)
                        <li class="px-5 py-3.5 text-sm sm:px-6">
                            <x-status-badge :status="$event->status" />
                            @if ($event->note) <p class="mt-1.5 text-ink-soft">“{{ $event->note }}”</p> @endif
                            <p class="mt-1 text-xs text-ink-muted">{{ $event->actor?->name ?? 'System' }} · {{ $event->created_at->format('j M Y, g:i a') }}</p>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Submission">
                <x-detail-list :columns="1" :items="[
                    'User' => $s->user->name,
                    'Request' => '#'.$s->request->id.' · '.str_replace('_', ' ', $s->request->status->value),
                    'Area' => $s->request->district->name,
                    'Submitted' => $s->created_at->format('j M Y, g:i a'),
                    'Reviewer' => $s->reviewer?->name ?? 'Not assigned',
                ]" />
            </x-card>

            @if ($s->status->isWaiting())
                <x-admin.decision title="Review decision" route="admin.verifications.decide" :param="$s->id" feature="Recording a review"
                    note-hint="The user sees this note. The donor only learns whether the check passed."
                    :options="[
                        'verified' => ['Verify', 'The prescription is valid. A reserved medicine can be handed over.'],
                        'reupload_required' => ['Ask for a new upload', 'The document is unclear or incomplete.'],
                        'rejected' => ['Reject', 'The document is not valid for this request.'],
                    ]" />
            @else
                <x-card title="Decision">
                    <p class="text-sm text-ink-soft">Decided by {{ $s->reviewer?->name }} on {{ $s->decided_at?->format('j M Y') }}.</p>
                    @if ($s->reviewer_note) <p class="mt-2 text-sm text-ink-soft">“{{ $s->reviewer_note }}”</p> @endif
                </x-card>
            @endif

            <x-admin.audit-list :entries="$audit" title="Audit" />
        </aside>
    </div>
</x-layouts.admin>
