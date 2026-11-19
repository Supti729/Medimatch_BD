<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => '01'.fake()->numberBetween(3, 9).fake()->unique()->numerify('########'),
            'phone_verified_at' => null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null, 'phone_verified_at' => null]);
    }

    /** An account registered with a phone number only. */
    public function phoneOnly(): static
    {
        return $this->state(fn () => ['email' => null, 'email_verified_at' => null, 'phone_verified_at' => now()]);
    }

    public function admin(): static
    {
        return $this->afterMaking(fn (User $user) => $user->role = Role::Admin);
    }

    public function reviewer(): static
    {
        return $this->afterMaking(fn (User $user) => $user->role = Role::Reviewer);
    }

    public function suspended(): static
    {
        return $this->afterMaking(fn (User $user) => $user->status = AccountStatus::Suspended);
    }
}
