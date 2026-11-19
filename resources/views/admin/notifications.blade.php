@php $ready = Route::has('admin.notifications.store'); @endphp

<x-layouts.admin title="Notifications" preview>
    <x-page-header title="Announcements" description="Send a platform-wide announcement. Each user receives it as a notification in their account." />

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_1fr]">
        <x-card title="New announcement">
            <x-not-connected route="admin.notifications.store" feature="Sending an announcement" class="mb-5" />

            <form class="space-y-5" @if ($ready) method="POST" action="{{ route('admin.notifications.store') }}" data-loading @else onsubmit="event.preventDefault()" @endif>
                @csrf
                <x-form.input name="title" label="Title" required maxlength="100" />
                <x-form.textarea name="message" label="Message" rows="4" required maxlength="500" hint="Keep it short. Do not include personal information." />
                <x-form.select name="audience" label="Send to" required
                    :options="['all' => 'All active users ('.$userCount.')', 'donors' => 'Users with active listings', 'requesters' => 'Users with open requests', 'partners' => 'Partner organizations']" />
                <x-button type="submit" icon="megaphone" :disabled="! $ready">Send announcement</x-button>
            </form>
            <x-slot:footer>
                <p class="text-xs text-ink-muted">Announcements are delivered inside the platform only. No email or SMS provider is configured.</p>
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
                                <p class="text-sm font-semibold">{{ $announcement['title'] }}</p>
                                <x-badge>{{ $announcement['audience'] }}</x-badge>
                            </div>
                            <p class="mt-1 text-sm text-ink-soft">{{ $announcement['message'] }}</p>
                            <p class="mt-1.5 text-xs text-ink-muted">{{ $announcement['sent_by'] }} · {{ $announcement['sent_at']->format('j M Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
