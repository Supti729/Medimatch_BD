{{--
    One notification row. Used in the header dropdown (compact) and on the notifications page.
    <x-notification-item :notification="$notification" />

    $notification keys: type, title, text, created_at, read, route [name, parameter?]
--}}
@props(['notification', 'compact' => false])

@php
    $icons = [
        'message' => ['chat-bubble-left-right', 'bg-info-50 text-info-700'],
        'match' => ['sparkles', 'bg-brand-50 text-brand-700'],
        'request' => ['hand-raised', 'bg-accent-50 text-accent-700'],
        'listing' => ['gift', 'bg-accent-50 text-accent-700'],
        'verification' => ['shield-check', 'bg-warning-50 text-warning-700'],
        'reservation' => ['clock', 'bg-info-50 text-info-700'],
        'completed' => ['check-circle', 'bg-success-50 text-success-700'],
        'report' => ['flag', 'bg-danger-50 text-danger-700'],
        'announcement' => ['megaphone', 'bg-brand-50 text-brand-700'],
    ];
    [$icon, $colour] = $icons[$notification['type']] ?? ['bell', 'bg-brand-50 text-brand-700'];

    $route = $notification['route'] ?? null;
    $url = $route && Route::has($route[0]) ? route($route[0], $route[1] ?? []) : null;
    $tag = $url ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($url) href="{{ $url }}" @endif
    {{ $attributes->class(['flex items-start gap-3', 'px-4 py-3' => $compact, 'p-4 sm:p-5' => ! $compact, 'hover:bg-brand-50/60' => $url, 'bg-brand-50/40' => ! $notification['read']]) }}>
    <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $colour }}" aria-hidden="true">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <span class="min-w-0 flex-1">
        <span @class(['block text-sm', 'font-semibold text-ink' => ! $notification['read'], 'font-medium text-ink-soft' => $notification['read']])>
            {{ $notification['title'] }}
        </span>
        <span @class(['mt-0.5 block text-sm text-ink-muted', 'line-clamp-1' => $compact])>{{ $notification['text'] }}</span>
        <span class="mt-1 block text-xs text-ink-muted">{{ $notification['created_at']->diffForHumans() }}</span>
    </span>
    @unless ($notification['read'])
        <span class="mt-1.5 size-2.5 shrink-0 rounded-full bg-accent-600" role="img" aria-label="Unread"></span>
    @endunless
</{{ $tag }}>
