<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\District;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo accounts for local development and presentations ONLY.
 * Refuses to run in production, because the password is public in this file.
 *
 *   admin@medimatch.test      Administrator
 *   reviewer@medimatch.test   Authorized reviewer
 *   user@medimatch.test       Regular user (email)
 *   01700000001               Regular user (phone only)
 *
 * Password for all of them: password
 */
class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DevelopmentUserSeeder must never run in production.');
        }

        $dhaka = District::where('name', 'Dhaka')->first();
        $area = [
            'division_id' => $dhaka?->division_id,
            'district_id' => $dhaka?->id,
            'upazila_id' => $dhaka?->upazilas()->where('name', 'Savar')->value('id'),
        ];

        $accounts = [
            ['Demo Administrator', 'admin@medimatch.test', null, Role::Admin],
            ['Demo Reviewer', 'reviewer@medimatch.test', null, Role::Reviewer],
            ['Demo User', 'user@medimatch.test', null, Role::User],
            ['Demo Phone User', null, '01700000001', Role::User],
        ];

        foreach ($accounts as [$name, $email, $phone, $role]) {
            $user = User::firstOrNew($email ? ['email' => $email] : ['phone' => $phone]);
            $user->fill(['name' => $name, 'email' => $email, 'phone' => $phone, 'password' => 'password'] + $area);
            $user->role = $role;
            $user->email_verified_at = $email ? now() : null;
            $user->phone_verified_at = $phone ? now() : null;
            $user->save();
        }
    }
}
