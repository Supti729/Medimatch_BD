<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Password rules used everywhere a password is chosen
        Password::defaults(fn () => Password::min(8)->max(255));

        // Administrators and authorized reviewers can open the admin panel
        Gate::define('access-admin', fn (User $user) => $user->role->isStaff() && ! $user->isSuspended());
    }
}
