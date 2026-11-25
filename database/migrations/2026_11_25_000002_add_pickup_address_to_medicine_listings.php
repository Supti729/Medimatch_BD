<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The donor's exact pick-up address. Stored encrypted (see MedicineListing casts), never shown publicly,
 * and only shown to the requester whose reservation the donor accepted.
 * Nullable because listings created before this column have no address.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_listings', function (Blueprint $table) {
            $table->text('pickup_address')->nullable()->after('upazila_id');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_listings', function (Blueprint $table) {
            $table->dropColumn('pickup_address');
        });
    }
};
