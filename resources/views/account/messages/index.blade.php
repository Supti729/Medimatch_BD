{{--
    Messages. One view for both pages:
    - /messages          list of conversations (on desktop the right pane asks you to pick one)
    - /messages/{id}     the conversation (on phones the list is hidden)
--}}
@php
    $me = auth()->user();
    $totalUnread = $conversations->sum('unread_count');
@endphp

<x-layouts.app :title="$current ? 'Conversation with '.$current->otherParticipant($me)->name : 'Messages'">
    <x-page-header title="Messages" description="Private conversations about your requests and listings. Phone numbers and email addresses are never shared." @class(['hidden lg:flex' => $current]) />

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[22rem_1fr]">
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
                    description="A conversation starts when you ask for a listing, or when someone asks for yours." />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($conversations as $conversation)
                        @php
                            $other = $conversation->otherParticipant($me);
                            $listing = $conversation->reservation->listing;
                            $active = $current && $current->id === $conversation->id;
                            $last = $conversation->latestMessage;
                        @endphp
                        <li>
                            <a href="{{ route('messages.show', $conversation) }}" @if ($active) aria-current="page" @endif
                                @class(['flex items-start gap-3 px-5 py-4 hover:bg-brand-50/60', 'bg-brand-50' => $active])>
                                <x-avatar :user="$other" size="sm" public />
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-baseline justify-between gap-2">
                                        <span @class(['truncate text-sm', 'font-semibold' => $conversation->unread_count, 'font-medium' => ! $conversation->unread_count])>{{ $other->name }}</span>
                                        @if ($last)
                                            <span class="shrink-0 text-xs text-ink-muted">{{ $last->created_at->shortAbsoluteDiffForHumans() }}</span>
                                        @endif
                                    </span>
                                    <span class="block truncate text-xs text-brand-700">{{ $listing->brand_name }} {{ $listing->medicine->strength }} · {{ $conversation->donor_id === $me->id ? 'your listing' : 'your request' }}</span>
                                    <span @class(['mt-0.5 block truncate text-sm', 'font-medium text-ink' => $conversation->unread_count, 'text-ink-muted' => ! $conversation->unread_count])>
                                        @if ($last)
                                            @if ($last->sender_id === $me->id) <span class="text-ink-muted">You:</span> @endif
                                            {{ $last->body }}
                                        @else
                                            <span class="italic">No messages yet</span>
                                        @endif
                                    </span>
                                </span>
                                @if ($conversation->unread_count)
                                    <span class="mt-1 flex min-w-5 items-center justify-center rounded-full bg-accent-600 px-1.5 text-xs font-semibold text-white">
                                        {{ $conversation->unread_count }}<span class="sr-only"> unread</span>
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
            @php
                $other = $current->otherParticipant($me);
                $listing = $current->reservation->listing;
                $open = $current->isOpen();
                $lastId = $current->messages->last()?->id ?? 0;
            @endphp
            <x-card :padded="false" class="flex min-h-[28rem] min-w-0 flex-col" aria-label="Conversation">
                <div class="flex items-center gap-3 border-b border-line px-4 py-3 sm:px-5">
                    <a href="{{ route('messages.index') }}" class="-ml-1.5 inline-flex size-10 items-center justify-center rounded-control text-ink-soft hover:bg-brand-50 lg:hidden">
                        <span class="sr-only">Back to conversations</span>
                        <x-icon name="arrow-left" class="size-5" />
                    </a>
                    <x-avatar :user="$other" size="sm" public />
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-base font-semibold">{{ $other->name }}</h2>
                        <a href="{{ $current->donor_id === $me->id ? route('listings.edit', $listing) : route('medicines.show', $listing->id) }}" class="block truncate text-xs font-medium text-brand-700 hover:underline">
                            {{ $listing->brand_name }} {{ $listing->medicine->strength }} · {{ $current->donor_id === $me->id ? 'your listing' : 'your request' }}
                        </a>
                    </div>
                    <span class="hidden shrink-0 sm:block"><x-badge :tone="$current->reservation->status->value === 'accepted' ? 'success' : ($open ? 'warning' : 'neutral')">{{ $current->reservation->status->label() }}</x-badge></span>
                </div>

                <ol id="message-list" class="max-h-[32rem] flex-1 space-y-3 overflow-y-auto bg-canvas/50 p-4 sm:p-5" aria-label="Messages" aria-live="polite"
                    data-poll-url="{{ route('messages.poll', $current) }}" data-last-id="{{ $lastId }}" data-other-name="{{ $other->name }}">
                    @forelse ($current->messages as $message)
                        @php $mine = $message->sender_id === $me->id; @endphp
                        <li @class(['flex', 'justify-end' => $mine]) data-message-id="{{ $message->id }}">
                            <div @class([
                                'max-w-[85%] rounded-card px-4 py-2.5 text-sm sm:max-w-[70%]',
                                'rounded-br-sm bg-brand-600 text-white' => $mine,
                                'rounded-bl-sm border border-line bg-surface text-ink' => ! $mine,
                            ])>
                                <span class="sr-only">{{ $mine ? 'You' : $other->name }}:</span>
                                <p class="break-words whitespace-pre-line">{{ $message->body }}</p>
                                <p @class(['mt-1 text-right text-[0.6875rem]', 'text-brand-100' => $mine, 'text-ink-muted' => ! $mine])>
                                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('j M, g:i a') }}</time>
                                    @if ($mine && $message->read_at) · Seen @endif
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="py-8 text-center text-sm text-ink-muted" data-empty>No messages yet. Say hello and agree a safe time and public place to meet.</li>
                    @endforelse
                </ol>
                <span id="latest"></span>

                <div class="border-t border-line p-4 sm:p-5">
                    @if ($open)
                        <form method="POST" action="{{ route('messages.store', $current) }}" class="flex items-end gap-2" data-loading>
                            @csrf
                            <div class="flex-1">
                                <x-form.textarea name="body" aria-label="Your message" rows="2" :maxlength="\App\Services\MessagingService::MAX_LENGTH" required placeholder="Write a message…" />
                            </div>
                            <x-button type="submit" icon="paper-airplane">Send</x-button>
                        </form>
                        <p class="mt-2 text-xs text-ink-muted">Text only. For your safety, phone numbers, email addresses and payment details cannot be shared. Meet in a public place.</p>
                    @else
                        <p class="text-center text-sm text-ink-muted">This conversation is closed because the handover has ended. You can still read it.</p>
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
