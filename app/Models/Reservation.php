<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One requester asking for one listing. Created and changed only through App\Services\ReservationService.
 */
class Reservation extends Model
{
    protected $fillable = ['message'];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'decided_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MedicineListing::class, 'medicine_listing_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MedicineRequest::class, 'medicine_request_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Accepted->value]);
    }

    /** Reservations where this user is either the donor or the requester. */
    public function scopeInvolving(Builder $query, User $user): void
    {
        $query->where(fn ($q) => $q->where('requester_id', $user->id)
            ->orWhereHas('listing', fn ($l) => $l->where('user_id', $user->id)));
    }

    public function isDonor(User $user): bool
    {
        return $this->listing->user_id === $user->id;
    }
}
