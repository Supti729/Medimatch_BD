<?php

use Database\Seeders\CityThanaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the 65 Dhaka and Chattogram city thanas to databases that were set up before they existed.
 * On a fresh install the location tables are still empty here, so this does nothing and LocationSeeder adds them.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new CityThanaSeeder)->run();
    }

    public function down(): void
    {
        // Thanas already used by a listing or request cannot be removed (those foreign keys restrict deletes).
        DB::table('upazilas')->where(fn ($q) => $q->where('code', 'like', 'DMP%')->orWhere('code', 'like', 'CMP%'))->delete();
    }
};
