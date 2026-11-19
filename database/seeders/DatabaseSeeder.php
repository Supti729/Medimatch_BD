<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate --seed
     */
    public function run(): void
    {
        $this->call(LocationSeeder::class);

        // Demo accounts with a known password: never in production.
        if (! app()->environment('production')) {
            $this->call(DevelopmentUserSeeder::class);
        }
    }
}
