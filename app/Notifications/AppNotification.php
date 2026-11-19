<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Base class for in-app notifications, stored in the "notifications" table.
 *
 * Only the database channel is used: no email or SMS is sent, because no provider is configured.
 * Every notification stores a type (for its icon), a title, a short text and a link.
 * Notifications are created synchronously (no queue worker needed).
 */
abstract class AppNotification extends Notification
{
    abstract protected function type(): string;

    abstract protected function title(): string;

    abstract protected function text(): string;

    /** Relative URL the notification opens. */
    abstract protected function url(): string;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'text' => $this->text(),
            'url' => $this->url(),
        ];
    }
}
