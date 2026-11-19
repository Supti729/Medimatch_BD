<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Support\Str;

class NewMessageNotification extends AppNotification
{
    public function __construct(public readonly Message $message) {}

    protected function type(): string
    {
        return 'message';
    }

    protected function title(): string
    {
        return 'New message from '.$this->message->sender->name;
    }

    protected function text(): string
    {
        return Str::limit($this->message->body, 120);
    }

    protected function url(): string
    {
        return route('messages.show', $this->message->conversation_id, false);
    }

    public function toArray(object $notifiable): array
    {
        return parent::toArray($notifiable) + ['conversation_id' => $this->message->conversation_id];
    }
}
