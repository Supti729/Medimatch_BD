<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A Medimatch BD account.
 *
 * Security: "role" and "status" are deliberately NOT fillable. They can only be
 * changed with an explicit assignment ($user->role = ...) in trusted server code,
 * never from form input.
 */
#[Fillable(['name', 'email', 'phone', 'password', 'division_id', 'district_id', 'upazila_id', 'avatar_path',
    'show_avatar', 'show_contributions', 'show_verified_badge', 'matched_messages_only'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'user',
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => AccountStatus::class,
            'show_avatar' => 'boolean',
            'show_contributions' => 'boolean',
            'show_verified_badge' => 'boolean',
            'matched_messages_only' => 'boolean',
        ];
    }

    // ----- Relationships -----

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

    public function listings(): HasMany
    {
        return $this->hasMany(MedicineListing::class);
    }

    // ----- Helpers -----

    /** Public URL of the profile picture, or null if there is none. */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /** Handovers this user completed as a donor (shown on the public profile if they allow it). */
    public function completedContributions(): int
    {
        return $this->listings()->where('status', 'completed')->count();
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::Suspended;
    }

    /**
     * An account counts as verified once its email address or its phone number is confirmed.
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null || $this->phone_verified_at !== null;
    }

    /**
     * Where verification codes and reset links go: email if there is one, otherwise the phone.
     */
    public function primaryChannel(): string
    {
        return $this->email ? 'email' : 'phone';
    }

    /**
     * Used by Laravel's password reset token storage. Phone-only accounts are keyed by their phone number.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email ?? $this->phone;
    }

    /**
     * "Upazila, District" for display, or null if the area is not set.
     */
    public function areaLabel(): ?string
    {
        return $this->district ? trim(($this->upazila?->name ? $this->upazila->name.', ' : '').$this->district->name) : null;
    }
}
