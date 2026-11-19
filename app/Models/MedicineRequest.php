<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A medicine a user needs.
 *
 * Security: user_id, status, expires_at and closed_at are NOT fillable; they are set by server code only.
 */
class MedicineRequest extends Model
{
    protected $fillable = ['medicine_id', 'quantity', 'unit', 'preference', 'notes', 'division_id', 'district_id', 'upazila_id'];

    protected $attributes = [
        'status' => 'active',
        'preference' => 'either',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(VerificationSubmission::class)->latest('id');
    }

    /** True once a reviewer has accepted a prescription for this request. */
    public function isPrescriptionVerified(): bool
    {
        return $this->verifications()->where('status', 'verified')->exists();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [RequestStatus::Active->value, RequestStatus::VerificationPending->value, RequestStatus::Reserved->value]);
    }

    /**
     * Move to another status. Silently does nothing if the move is not allowed,
     * because several automatic updates (e.g. a declined reservation) may try to reset a request.
     */
    public function moveTo(RequestStatus $target): bool
    {
        if (! $this->status->canTransitionTo($target)) {
            return false;
        }

        $this->status = $target;
        $this->closed_at = in_array($target, [RequestStatus::Completed, RequestStatus::Cancelled, RequestStatus::Expired], true) ? now() : null;
        $this->save();

        return true;
    }
}
