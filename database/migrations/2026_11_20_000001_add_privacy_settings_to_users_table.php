<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a user chooses to show on their public profile.
 * Contact details, address and documents are never public, so they have no setting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_avatar')->default(true)->after('avatar_path');
            $table->boolean('show_contributions')->default(true)->after('show_avatar');
            $table->boolean('show_verified_badge')->default(true)->after('show_contributions');
            $table->boolean('matched_messages_only')->default(true)->after('show_verified_badge');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['show_avatar', 'show_contributions', 'show_verified_badge', 'matched_messages_only']);
        });
    }
};
