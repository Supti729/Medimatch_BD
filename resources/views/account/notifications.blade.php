<x-layouts.app title="Notifications">
    <x-page-header title="Notifications" :description="$unreadCount ? $unreadCount.' unread' : 'You are all caught up.'">
        <form method="POST" action="{{ route('notifications.read-all') }}" data-loading>
            @csrf
            <x-button type="submit" variant="secondary" icon="check" :disabled="! $unreadCount">Mark all as read</x-button>
        </form>
    </x-page-header>

    <x-tabs class="mt-6" route="notifications.index" param="filter" :current="$filter" :tabs="['all' => 'All', 'unread' => 'Unread']" :counts="['unread' => $unreadCount]" />

    <x-card class="mt-6" :padded="false">
        @if ($notifications->isEmpty())
            <x-empty-state icon="bell" :title="$filter === 'unread' ? 'No unread notifications' : 'No notifications yet'"
                description="You are told here when someone asks for your medicine, answers your request, sends you a message, or a match is found." />
        @else
            <ul class="divide-y divide-line">
                @foreach ($notifications as $notification)
                    <li><x-notification-item :notification="$notification" /></li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="mt-6">{{ $notifications->links('partials.pagination') }}</div>

    <p class="mt-3 text-sm text-ink-muted">Notifications appear in the app only. No email or SMS is sent, because no provider is configured.</p>
</x-layouts.app>
