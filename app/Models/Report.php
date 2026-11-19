<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    /** Reasons a user can choose, with the label shown to moderators. */
    public const REASONS = [
        'unsafe' => 'Unsafe or damaged medicine',
        'expired' => 'Expired medicine',
        'misleading' => 'Wrong or misleading details',
        'selling' => 'Asking for money for a donation',
        'contact_details' => 'Asking for personal or contact details',
        'harassment' => 'Rude or threatening messages',
        'other' => 'Something else',
    ];

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['status' => ReportStatus::class, 'resolved_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** The reported listing or conversation (may have been deleted). */
    public function target(): ?Model
    {
        return match ($this->target_type) {
            'listing' => MedicineListing::with(['medicine', 'owner', 'images'])->find($this->target_id),
            'conversation' => Conversation::with(['donor', 'requester', 'reservation.listing.medicine'])->find($this->target_id),
            default => null,
        };
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst($this->reason);
    }
}
