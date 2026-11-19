<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An uploaded prescription waiting for, or decided by, an authorized reviewer.
 * The file lives on the private disk; its path is never sent to the browser.
 */
class VerificationSubmission extends Model
{
    protected $guarded = ['*'];

    protected $hidden = ['document_path'];

    protected function casts(): array
    {
        return ['status' => VerificationStatus::class, 'decided_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MedicineRequest::class, 'medicine_request_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VerificationEvent::class)->orderBy('id');
    }
}
