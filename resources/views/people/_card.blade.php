{{-- The public profile card. Only shows what $person allows. Used on the public profile and the profile preview. --}}
@php $completed = $person->show_contributions ? $person->completedContributions() : null; @endphp

<div class="rounded-card border border-dashed border-line bg-canvas p-5">
    <div class="flex items-center gap-4">
        <x-avatar :user="$person" size="lg" public />
        <div class="min-w-0 flex-1">
            <p class="truncate text-base font-semibold">{{ $person->name }}</p>
            <p class="text-sm text-ink-muted">Member since {{ $person->created_at->format('M Y') }}</p>
        </div>
        @if ($person->show_verified_badge && $person->isVerified())
            <x-badge tone="success">Verified</x-badge>
        @endif
    </div>
    @if ($completed !== null)
        <p class="mt-4 border-t border-line pt-4 text-sm text-ink-soft">
            <span class="font-semibold text-ink">{{ $completed }}</span> completed {{ Str::plural('donation', $completed) }}
        </p>
    @endif
</div>
