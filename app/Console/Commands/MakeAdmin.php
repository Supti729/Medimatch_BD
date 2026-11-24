<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\AccountLookup;
use Illuminate\Console\Command;

/**
 * Give an existing, verified account administrator access. This is the ONLY way to create an
 * administrator: the admin panel can never do it.
 *
 *   php artisan app:make-admin someone@example.com
 */
class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {login : Email address or phone number of an existing account}';

    protected $description = 'Give an existing account administrator access (server only)';

    public function handle(): int
    {
        $user = AccountLookup::find($this->argument('login'));

        if (! $user) {
            $this->error('No account found. The person must register first.');

            return self::FAILURE;
        }
        if (! $user->isVerified() || $user->isSuspended()) {
            $this->error('The account must be verified and active.');

            return self::FAILURE;
        }
        if ($user->isAdmin()) {
            $this->info("{$user->name} is already an administrator.");

            return self::SUCCESS;
        }
        if (! $this->confirm("Give {$user->name} (user #{$user->id}) full administrator access?")) {
            return self::FAILURE;
        }

        $user->role = Role::Admin;
        $user->save();
        AuditLog::record('user.made_admin', $user, "Made user #{$user->id} {$user->name} an administrator from the server console");

        $this->info("{$user->name} is now an administrator.");

        return self::SUCCESS;
    }
}
