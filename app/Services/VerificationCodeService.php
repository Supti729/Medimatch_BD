<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * One-time 6-digit codes that confirm a user owns their email address or phone number.
 *
 * - Only a hash of the code is stored (in the cache), never the code itself.
 * - A code expires after a few minutes and allows a limited number of attempts.
 * - Email codes are sent with Laravel Notifications (to the log file in development).
 * - SMS is NOT configured, so phone codes are not sent anywhere. In local development
 *   the code can be shown on screen instead (see config/medimatch.php).
 */
class VerificationCodeService
{
    /**
     * Create and deliver a new code.
     *
     * @return array{channel: string, delivered: bool, dev_code: ?string}
     */
    public function send(User $user): array
    {
        $code = (string) random_int(100000, 999999);
        $channel = $user->primaryChannel();

        Cache::put($this->key($user), [
            'hash' => Hash::make($code),
            'channel' => $channel,
            'attempts' => 0,
        ], now()->addMinutes(config('medimatch.verification_code_minutes')));

        $delivered = false;
        if ($channel === 'email') {
            $user->notify(new VerificationCodeNotification($code));
            $delivered = true;
        }

        return [
            'channel' => $channel,
            'delivered' => $delivered,
            'dev_code' => config('medimatch.dev_codes_on_screen') ? $code : null,
        ];
    }

    /**
     * Check a code. On success the matching channel is marked as verified.
     *
     * @return 'verified'|'invalid'|'expired'|'too_many_attempts'
     */
    public function verify(User $user, string $code): string
    {
        $entry = Cache::get($this->key($user));

        if ($entry === null) {
            return 'expired';
        }

        if ($entry['attempts'] >= config('medimatch.verification_code_attempts')) {
            Cache::forget($this->key($user));

            return 'too_many_attempts';
        }

        if (! Hash::check($code, $entry['hash'])) {
            $entry['attempts']++;
            Cache::put($this->key($user), $entry, now()->addMinutes(config('medimatch.verification_code_minutes')));

            return 'invalid';
        }

        Cache::forget($this->key($user));

        if ($entry['channel'] === 'email') {
            $user->email_verified_at = now();
        } else {
            $user->phone_verified_at = now();
        }
        $user->save();

        return 'verified';
    }

    private function key(User $user): string
    {
        return 'verification-code:'.$user->getKey();
    }
}
