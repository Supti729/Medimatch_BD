<x-layouts.admin title="Verification" preview>
    <x-page-header title="Verification center" description="Prescription documents waiting for an authorized reviewer. Only reviewers can open documents, and every view is recorded." />

    <x-alert tone="warning" title="Sensitive documents" class="mt-6">
        Open a document only when you are reviewing it. Never download, copy or share it, and never use AI to approve or reject a prescription.
    </x-alert>

    <x-tabs class="mt-6" route="admin.verifications.index" :current="$tab" :counts="$counts"
        :tabs="['waiting' => 'Waiting', 'decided' => 'Decided', 'all' => 'All']" />

    <x-card class="mt-6" :padded="false">
        @if ($submissions->isEmpty())
            <x-empty-state icon="document-check" title="Nothing to review" description="New submissions will appear here." />
        @else
            <x-table :headers="['Submission', 'For request', 'User', 'Submitted', 'Reviewer', 'Status', '']" caption="Verification submissions">
                @foreach ($submissions as $submission)
                    <tr class="hover:bg-brand-50/40">
                        <th scope="row" class="px-5 py-3.5 font-medium whitespace-nowrap">#{{ $submission['id'] }}</th>
                        <td class="px-5 py-3.5">{{ $submission['request'] }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $submission['user']['name'] }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $submission['submitted_at']->diffForHumans() }}</td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-ink-soft">{{ $submission['reviewer'] ?? 'Not assigned' }}</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$submission['status']" /></td>
                        <td class="px-5 py-3.5 text-right"><x-button :href="route('admin.verifications.show', $submission['id'])" size="sm" variant="secondary">Review</x-button></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-layouts.admin>
