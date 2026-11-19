<x-layouts.admin title="Announcements">
    <x-page-header title="Announcements" description="Send a message to many users at once. It arrives as a notification in their account." />

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card title="New announcement">
            <form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-5" data-loading>
                @csrf
                <x-form.input name="title" label="Title" required maxlength="100" />
                <x-form.textarea name="message" label="Message" rows="4" required maxlength="500" hint="Keep it short. Do not include personal information." />
                <x-form.select name="audience" label="Send to" required :options="$audiences" />
                <x-button type="submit" icon="megaphone">Send announcement</x-button>
            </form>
            <x-slot:footer>
                <p class="text-xs text-ink-muted">Delivered inside the app only. No email or SMS provider is configured.</p>
            </x-slot:footer>
        </x-card>

        <x-card title="Sent announcements" :padded="false">
            @if ($announcements->isEmpty())
                <x-empty-state icon="megaphone" title="Nothing sent yet" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($announcements as $announcement)
                        <li class="px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="text-sm font-semibold">{{ $announcement->title }}</p>
                                <x-badge>{{ \App\Models\Announcement::AUDIENCES[$announcement->audience] ?? $announcement->audience }}</x-badge>
                            </div>
                            <p class="mt-1 text-sm text-ink-soft">{{ $announcement->message }}</p>
                            <p class="mt-1.5 text-xs text-ink-muted">{{ $announcement->sender?->name ?? 'Administrator' }} · {{ $announcement->created_at->format('j M Y, g:i a') }} · {{ $announcement->recipients_count }} {{ Str::plural('recipient', $announcement->recipients_count) }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
