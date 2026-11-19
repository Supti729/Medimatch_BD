@php $decided = in_array($submission['status'], ['verified', 'rejected'], true); @endphp

<x-layouts.admin :title="'Verification #'.$submission['id']" preview>
    <a href="{{ route('admin.verifications.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-brand-700"><x-icon name="arrow-left" class="size-4" /> Verification</a>

    <x-page-header class="mt-3" :title="'Document review #'.$submission['id']" :description="$submission['request']">
        <x-status-badge :status="$submission['status']" />
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <x-card title="Document" description="Shown only to authorized reviewers. It is served from private storage through a short-lived link.">
                <div class="flex min-h-72 flex-col items-center justify-center rounded-control border border-dashed border-line bg-canvas p-6 text-center">
                    <x-icon name="document-text" class="size-10 text-ink-muted" />
                    <p class="mt-3 text-sm font-semibold">No document in the design preview</p>
                    <p class="mt-1 max-w-sm text-sm text-ink-muted">The prototype never stores real prescriptions. In the backend part, the uploaded file is shown here after the reviewer's access is checked and logged.</p>
                </div>
            </x-card>

            <x-card title="What to check">
                <ul class="space-y-2.5 text-sm text-ink-soft">
                    @foreach ([
                        'The prescription is from a registered doctor, with a readable name and registration number.',
                        'The date is recent enough for the medicine.',
                        'The medicine, strength and form match the request.',
                        'The quantity requested is not more than prescribed.',
                        'The document does not look altered.',
                    ] as $point)
                        <li class="flex items-start gap-2.5"><x-icon name="check" class="mt-0.5 size-5 text-brand-600" /> {{ $point }}</li>
                    @endforeach
                </ul>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Submission">
                <x-detail-list :columns="1" :items="[
                    'User' => $submission['user']['name'],
                    'Request' => $submission['request'],
                    'Document type' => $submission['document'],
                    'Submitted' => $submission['submitted_at']->format('j M Y, g:i a'),
                    'Reviewer' => $submission['reviewer'] ?? 'Not assigned',
                ]" />
                @if ($submission['note'])
                    <x-alert tone="info" title="Reviewer note" class="mt-5">{{ $submission['note'] }}</x-alert>
                @endif
            </x-card>

            @if ($decided)
                <x-card title="Decision">
                    <p class="text-sm text-ink-soft">This submission has been decided. A new upload by the user starts a new review.</p>
                </x-card>
            @else
                <x-admin.decision title="Review decision" route="admin.verifications.decide" :param="$submission['id']" feature="Recording a review"
                    note-hint="The user sees this note. The donor only learns whether the check passed."
                    :options="[
                        'verified' => ['Verify', 'The document is valid. The handover can go ahead.'],
                        'reupload_required' => ['Ask for a new upload', 'The document is unclear or incomplete.'],
                        'rejected' => ['Reject', 'The document is not valid for this request.'],
                    ]" />
            @endif

            <x-admin.audit-list :entries="$audit" title="Review history" />
        </aside>
    </div>
</x-layouts.admin>
