{{-- Short list of audit entries about one record. <x-admin.audit-list :entries="$audit" /> --}}
@props(['entries', 'title' => 'Moderation history'])

<x-card :title="$title" :padded="false">
    @if ($entries->isEmpty())
        <p class="p-5 text-sm text-ink-muted sm:p-6">No actions have been recorded for this record.</p>
    @else
        <ul class="divide-y divide-line">
            @foreach ($entries as $entry)
                <li class="px-5 py-3.5 text-sm sm:px-6">
                    <p><code class="rounded bg-canvas px-1.5 py-0.5 text-xs text-ink">{{ $entry['action'] }}</code></p>
                    <p class="mt-1 text-xs text-ink-muted">{{ $entry['actor'] }} · {{ $entry['at']->format('j M Y, g:i a') }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
