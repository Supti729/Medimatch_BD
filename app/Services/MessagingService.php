<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Support\ContactDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Private messages between the donor and the requester of a reservation.
 *
 * Rules:
 *   - a conversation exists only for a reservation (opened when the request is made)
 *   - only its two participants can read it or write in it
 *   - new messages only while the reservation is pending or accepted; afterwards it is read-only
 *   - text only (no attachments), 1-1000 characters, no phone numbers or email addresses
 */
class MessagingService
{
    public const MAX_LENGTH = 1000;

    /** Open the conversation for a new reservation, with the requester's note as the first message. */
    public function openFor(Reservation $reservation): Conversation
    {
        $conversation = new Conversation;
        $conversation->reservation_id = $reservation->id;
        $conversation->donor_id = $reservation->listing->user_id;
        $conversation->requester_id = $reservation->requester_id;
        $conversation->save();

        if (filled($reservation->message)) {
            $this->store($conversation, $reservation->requester, $reservation->message, notify: false);
        }

        return $conversation;
    }

    public function send(Conversation $conversation, User $sender, string $body): Message
    {
        abort_unless($conversation->hasParticipant($sender), 403);

        $body = trim(preg_replace("/\r\n?/", "\n", $body));

        if (! $conversation->isOpen()) {
            throw ValidationException::withMessages(['body' => 'This conversation is closed because the handover has ended.']);
        }
        if ($body === '' || mb_strlen($body) > self::MAX_LENGTH) {
            throw ValidationException::withMessages(['body' => 'Write a message of up to '.self::MAX_LENGTH.' characters.']);
        }
        if (ContactDetails::found($body)) {
            throw ValidationException::withMessages(['body' => 'For your safety, please do not share phone numbers or email addresses. Agree the handover here and meet in a public place.']);
        }

        return DB::transaction(fn () => $this->store($conversation, $sender, $body));
    }

    /** Mark the other person's messages as read. */
    public function markRead(Conversation $conversation, User $reader): void
    {
        $conversation->messages()->where('sender_id', '!=', $reader->id)->whereNull('read_at')->update(['read_at' => now()]);

        // Also clear the "new message" notifications for this conversation
        $reader->unreadNotifications()
            ->where('type', NewMessageNotification::class)
            ->get()
            ->filter(fn ($n) => ($n->data['conversation_id'] ?? null) === $conversation->id)
            ->each->markAsRead();
    }

    private function store(Conversation $conversation, User $sender, string $body, bool $notify = true): Message
    {
        $message = new Message(['body' => $body]);
        $message->conversation_id = $conversation->id;
        $message->sender_id = $sender->id;
        $message->save();

        $conversation->last_message_at = $message->created_at;
        $conversation->save();

        if ($notify) {
            $recipient = $conversation->otherParticipant($sender);

            // One unread "new message" notification per conversation is enough.
            $alreadyNotified = $recipient->unreadNotifications()
                ->where('type', NewMessageNotification::class)
                ->get()
                ->contains(fn ($n) => ($n->data['conversation_id'] ?? null) === $conversation->id);

            if (! $alreadyNotified) {
                $recipient->notify(new NewMessageNotification($message->setRelation('sender', $sender)));
            }
        }

        return $message;
    }
}
