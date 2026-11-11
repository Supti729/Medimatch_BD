{{--
    Messages. One view for both pages:
    - /messages          list of conversations (on desktop the right pane asks you to pick one)
    - /messages/{id}     the conversation (on phones the list is hidden)
--}}
@php
    $ready = Route::has('messages.store');
    $totalUnread = $conversations->sum('unread');
@endphp

<x-layouts.app :title="$current ? 'Conversation with '.$current['with'] : 'Messages'" :preview="$usingSampleData">
    <x-page-header title="Messages" description="Private conversations about your listings and requests. Phone numbers and email addresses are never shared." @class(['hidden lg:flex' => $current]) />

    <div class="mt-6 grid gap-6 lg:grid-cols-[22rem_1fr]">
        {{-- Conversation list --}}
        <x-card :padded="false" @class(['min-w-0', 'hidden lg:block' => $current]) as="aside" aria-label="Conversations">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-base font-semibold">Conversations</h2>
                @if ($totalUnread)
                    <x-badge tone="accent">{{ $totalUnread }} unread</x-badge>
                @endif
            </div>

            @if ($conversations->isEmpty())
                <x-empty-state icon="chat-bubble-left-right" title="No conversations yet"
                    description="A conversation starts when a request and a listing are matched." />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($conversations as $conversation)
                        @php $active = $current && $current['id'] === $conversation['id']; @endphp
                        <li>
                            <a href="{{ route('messages.show', $conversation['id']) }}" @if ($active) aria-current="page" @endif
                                @class(['flex items-start gap-3 px-5 py-4 hover:bg-brand-50/60', 'bg-brand-50' => $active])>
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white" aria-hidden="true">{{ mb_substr(Str::afterLast($conversation['with'], ' '), 0, 1) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-baseline justify-between gap-2">
                                        <span @class(['truncate text-sm', 'font-semibold' => $conversation['unread'], 'font-medium' => ! $conversation['unread']])>{{ $conversation['with'] }}</span>
                                        <span class="shrink-0 text-xs text-ink-muted">{{ $conversation['last']['sent_at']->shortAbsoluteDiffForHumans() }}</span>
                                    </span>
                                    <span class="block truncate text-xs text-brand-700">{{ $conversation['about']['label'] }}</span>
                                    <span @class(['mt-0.5 block truncate text-sm', 'font-medium text-ink' => $conversation['unread'], 'text-ink-muted' => ! $conversation['unread']])>
                                        @if ($conversation['last']['mine']) <span class="text-ink-muted">You:</span> @endif
                                        {{ $conversation['last']['body'] }}
                                    </span>
                                </span>
                                @if ($conversation['unread'])
                                    <span class="mt-1 flex min-w-5 items-center justify-center rounded-full bg-accent-600 px-1.5 text-xs font-semibold text-white">
                                        {{ $conversation['unread'] }}<span class="sr-only"> unread</span>
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        {{-- Conversation --}}
        @if ($current)
            <x-card :padded="false" class="flex min-h-[28rem] min-w-0 flex-col" aria-label="Conversation">
                <div class="flex items-center gap-3 border-b border-line px-4 py-3 sm:px-5">
                    <a href="{{ route('messages.index') }}" class="-ml-1.5 inline-flex size-10 items-center justify-center rounded-control text-ink-soft hover:bg-brand-50 lg:hidden">
                        <span class="sr-only">Back to conversations</span>
                        <x-icon name="arrow-left" class="size-5" />
                    </a>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white" aria-hidden="true">{{ mb_substr(Str::afterLast($current['with'], ' '), 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-base font-semibold">{{ $current['with'] }}</h2>
                        <p class="flex flex-wrap items-center gap-x-2 text-xs text-ink-muted">
                            @if (Route::has($current['about']['route'][0]))
                                <a href="{{ route(...$current['about']['route']) }}" class="truncate font-medium text-brand-700 hover:underline">{{ $current['about']['label'] }}</a>
                            @else
                                <span class="truncate">{{ $current['about']['label'] }}</span>
                            @endif
                        </p>
                    </div>
                    <span class="hidden shrink-0 sm:block"><x-status-badge :status="$current['status']" /></span>
                    <x-dropdown>
                        <x-slot:trigger>
                            <button type="button" data-dropdown-trigger aria-haspopup="true" aria-expanded="false" class="inline-flex size-10 items-center justify-center rounded-control text-ink-soft hover:bg-brand-50">
                                <span class="sr-only">Conversation options</span>
                                <x-icon name="ellipsis-vertical" class="size-5" />
                            </button>
                        </x-slot:trigger>
                        <x-dropdown.item icon="flag" :disabled="! Route::has('reports.store')">Report conversation</x-dropdown.item>
                        @unless (Route::has('reports.store'))
                            <p class="border-t border-line px-4 pt-2 pb-1 text-xs text-ink-muted">Reporting is not connected yet.</p>
                        @endunless
                    </x-dropdown>
                </div>

                <ol class="flex-1 space-y-3 overflow-y-auto bg-canvas/50 p-4 sm:p-5" aria-label="Messages">
                    @foreach ($current['messages'] as $message)
                        <li @class(['flex', 'justify-end' => $message['mine']])>
                            <div @class([
                                'max-w-[85%] rounded-card px-4 py-2.5 text-sm sm:max-w-[70%]',
                                'rounded-br-sm bg-brand-600 text-white' => $message['mine'],
                                'rounded-bl-sm border border-line bg-surface text-ink' => ! $message['mine'],
                            ])>
                                <span class="sr-only">{{ $message['mine'] ? 'You' : $current['with'] }}:</span>
                                <p class="break-words whitespace-pre-line">{{ $message['body'] }}</p>
                                <p @class(['mt-1 text-right text-[0.6875rem]', 'text-brand-100' => $message['mine'], 'text-ink-muted' => ! $message['mine']])>
                                    <time datetime="{{ $message['sent_at']->toIso8601String() }}">{{ $message['sent_at']->format('j M, g:i a') }}</time>
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="border-t border-line p-4 sm:p-5">
                    @if ($current['status'] === 'completed')
                        <p class="text-center text-sm text-ink-muted">This handover is completed, so the conversation is closed.</p>
                    @else
                        <form class="flex items-end gap-2" @if ($ready) method="POST" action="{{ route('messages.store', $current['id']) }}" data-loading @else onsubmit="event.preventDefault()" @endif>
                            @csrf
                            <div class="flex-1">
                                <x-form.textarea name="body" aria-label="Your message" rows="2" maxlength="1000" required placeholder="Write a message…" />
                            </div>
                            <x-button type="submit" icon="paper-airplane" :disabled="! $ready">Send</x-button>
                        </form>
                        <p class="mt-2 text-xs text-ink-muted">
                            @unless ($ready) Sending is not connected yet. @endunless
                            Never share your phone number, address or payment details in messages.
                        </p>
                    @endif
                </div>
            </x-card>
        @else
            <x-card :padded="false" class="hidden lg:block">
                <x-empty-state icon="chat-bubble-left-right" title="Select a conversation" description="Choose a conversation on the left to read and reply." class="py-24" />
            </x-card>
        @endif
    </div>
</x-layouts.app>
