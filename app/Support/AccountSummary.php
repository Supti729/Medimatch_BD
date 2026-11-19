<?php

namespace App\Support;

use App\Models\User;

/**
 * The signed-in user's real account details, in the array shape the account pages already use.
 *
 * Privacy settings are not stored yet (push B2), so the safe defaults are shown.
 */
class AccountSummary
{
    public static function for(User $user): array
    {
        $user->loadMissing(['division', 'district', 'upazila']);

        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'division' => $user->division?->name,
            'district' => $user->district?->name,
            'upazila' => $user->upazila?->name,
            'joined' => $user->created_at,
            'verified' => $user->isVerified(),
            'privacy' => [
                'show_avatar' => true,
                'show_contributions' => true,
                'show_verified_badge' => true,
                'matched_messages_only' => true,
            ],
        ];
    }
}
