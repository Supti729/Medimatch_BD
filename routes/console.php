<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Close requests that passed their expiry date (needs the Laravel scheduler running: php artisan schedule:work)
Schedule::command('requests:expire')->daily();
