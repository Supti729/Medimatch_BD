<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A private conversation between the donor and the requester of one reservation.
 * Created only by MessagingService::openFor(); never from user input.
 */
class Conversation extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeFor(Builder $query, User $user): void
    {
        $query->where(fn ($q) => $q->where('donor_id', $user->id)->orWhere('requester_id', $user->id));
    }

    public function hasParticipant(User $user): bool
    {
        return $this->donor_id === $user->id || $this->requester_id === $user->id;
    }

    public function otherParticipant(User $user): User
    {
        return $this->donor_id === $user->id ? $this->requester : $this->donor;
    }

    /** New messages can only be sent while the reservation is pending or accepted. */
    public function isOpen(): bool
    {
        return $this->reservation->status->isActive();
    }

    public function unreadCountFor(User $user): int
    {
        return $this->messages()->where('sender_id', '!=', $user->id)->whereNull('read_at')->count();
    }
}
