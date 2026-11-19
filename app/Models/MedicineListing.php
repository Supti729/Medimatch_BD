<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidStatusTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A medicine offered by a user.
 *
 * Security: user_id, status, review_note and the timestamps are NOT fillable.
 * The owner is set from the logged-in user, and the status only changes through transitionTo().
 */
class MedicineListing extends Model
{
    protected $fillable = [
        'medicine_id', 'medicine_alias_id', 'brand_name', 'quantity', 'unit', 'expiry_date',
        'batch_number', 'packaging', 'type', 'division_id', 'district_id', 'upazila_id',
    ];

    protected $attributes = [
        'status' => 'draft',
        'type' => 'donation',
    ];

    protected function casts(): array
    {
        return [
            'status' => ListingStatus::class,
            'expiry_date' => 'date',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    // ----- Relationships -----

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function alias(): BelongsTo
    {
        return $this->belongsTo(MedicineAlias::class, 'medicine_alias_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('position');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
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

    // ----- Scopes -----

    /** Listings anyone may see. Blocked, rejected, draft and unreviewed listings are never included. */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->whereIn('status', array_map(fn (ListingStatus $s) => $s->value, ListingStatus::publicStatuses()));
    }

    // ----- Status -----

    /**
     * The only way to change a listing's status. Throws if the move is not allowed.
     */
    public function transitionTo(ListingStatus $target, ?string $note = null): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::make($this->status->value, $target->value);
        }

        $this->status = $target;

        if ($target === ListingStatus::PendingReview) {
            $this->submitted_at = now();
            $this->review_note = null;
        }

        if ($target === ListingStatus::Available && $this->published_at === null) {
            $this->published_at = now();
        }

        if ($note !== null) {
            $this->review_note = $note;
        }

        $this->save();
    }

    // ----- Display helpers -----

    /**
     * The array shape used by <x-medicine-card> and the public details page.
     * Load medicine.aliases, alias, upazila, district, division and owner first to avoid extra queries.
     */
    public function toCard(): array
    {
        $owner = $this->owner;

        return [
            'id' => $this->id,
            'name' => $this->brand_name,
            'generic_name' => $this->medicine->generic_name,
            'strength' => $this->medicine->strength,
            'dosage_form' => $this->medicine->dosage_form,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'expiry' => $this->expiry_date->format('Y-m'),
            'type' => $this->type,
            'status' => $this->status->value,
            'division' => $this->division->name,
            'district' => $this->district->name,
            'area' => $this->upazila->name,
            'posted_at' => $this->published_at ?? $this->created_at,
            'requires_prescription' => $this->medicine->requires_prescription,
            'manufacturer' => $this->alias?->manufacturer ?? 'Not recorded',
            'packaging' => config('medimatch.packaging')[$this->packaging] ?? $this->packaging,
            'image' => $this->relationLoaded('images') ? $this->images->first()?->url() : null,
            'donor' => [
                'id' => $owner->id,
                'name' => $owner->name,
                'joined' => $owner->created_at,
                // Only shown when the donor allows it
                'verified' => $owner->show_verified_badge && $owner->isVerified(),
            ],
        ];
    }

    /** Relations needed by toCard(). */
    public const CARD_RELATIONS = ['medicine', 'alias', 'upazila', 'district', 'division', 'owner', 'images'];

    public function monthsUntilExpiry(): int
    {
        return (int) floor(now()->startOfMonth()->diffInMonths($this->expiry_date->copy()->startOfMonth(), false));
    }

    public static function expiryFromMonth(string $yearMonth): Carbon
    {
        return Carbon::createFromFormat('!Y-m', $yearMonth)->startOfMonth();
    }
}
