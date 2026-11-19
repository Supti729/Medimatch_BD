<?php

namespace App\Notifications;

/**
 * A notification whose wording is decided by the code that sends it
 * (moderation decisions, verification results, report outcomes, announcements).
 */
class SimpleNotification extends AppNotification
{
    public function __construct(
        private readonly string $kind,
        private readonly string $heading,
        private readonly string $body,
        private readonly string $link,
    ) {}

    protected function type(): string
    {
        return $this->kind;
    }

    protected function title(): string
    {
        return $this->heading;
    }

    protected function text(): string
    {
        return $this->body;
    }

    protected function url(): string
    {
        return $this->link;
    }
}
