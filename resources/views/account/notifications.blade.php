@php $connected = Route::has('notifications.read-all'); @endphp

<x-layouts.app title="Notifications" :preview="$usingSampleData">
    <x-page-header title="Notifications" :description="$unreadCount ? $unreadCount.' unread' : 'You are all caught up.'">
        @if ($connected)
            <form method="POST" action="{{ route('notifications.read-all') }}" data-loading>
                @csrf
                <x-button type="submit" variant="secondary" icon="check" :disabled="! $unreadCount">Mark all as read</x-button>
            </form>
        @else
            <x-button variant="secondary" icon="check" disabled title="Not connected yet">Mark all as read</x-button>
        @endif
    </x-page-header>

    <x-tabs class="mt-6" route="notifications.index" param="filter" :current="$filter" :tabs="['all' => 'All', 'unread' => 'Unread']" :counts="['unread' => $unreadCount]" />

    <x-card class="mt-6" :padded="false">
        @if ($notifications->isEmpty())
            <x-empty-state icon="bell" :title="$filter === 'unread' ? 'No unread notifications' : 'No notifications yet'"
                description="Updates about your listings, requests, messages and verification will appear here." />
        @else
            <ul class="divide-y divide-line">
                @foreach ($notifications as $notification)
                    <li><x-notification-item :notification="$notification" /></li>
                @endforeach
            </ul>
        @endif
    </x-card>

    @unless ($connected)
        <p class="mt-3 text-sm text-ink-muted">Marking notifications as read is not connected yet. It works once the notification system is added in the backend part.</p>
    @endunless
</x-layouts.app>
