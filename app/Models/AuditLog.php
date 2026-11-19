<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only audit trail. Use AuditLog::record(); rows can never be updated or deleted through the app.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Record an action by the signed-in user (or the system when nobody is signed in).
     *
     * AuditLog::record('listing.approved', $listing, 'Approved listing #12 Napa 500 mg', ['note' => '...']);
     */
    public static function record(string $action, ?Model $subject, string $description, array $meta = []): self
    {
        $log = new self;
        $log->actor_id = auth()->id();
        $log->action = $action;
        $log->subject_type = $subject ? str(class_basename($subject))->snake()->replace('medicine_', '')->toString() : null;
        $log->subject_id = $subject?->getKey();
        $log->description = mb_substr($description, 0, 255);
        $log->meta = $meta ?: null;
        $log->ip_address = request()?->ip();
        $log->save();

        return $log;
    }
}
