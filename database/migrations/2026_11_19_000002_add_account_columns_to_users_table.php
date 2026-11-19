<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medimatch BD account fields.
 *
 * - Users sign up with an email address, a Bangladeshi mobile number, or both,
 *   so email becomes optional and phone is added. Both stay unique.
 * - role and status are never mass-assignable; only the server sets them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();

            // Stored in one normalised form: 01XXXXXXXXX (11 digits)
            $table->string('phone', 11)->nullable()->unique()->after('email_verified_at');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');

            $table->string('role', 20)->default('user')->index()->after('password');
            $table->string('status', 20)->default('active')->index()->after('role');

            $table->foreignId('division_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('division_id')->constrained()->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->after('district_id')->constrained()->nullOnDelete();

            $table->string('avatar_path')->nullable()->after('upazila_id');
            $table->timestamp('last_login_at')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upazila_id');
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('division_id');
            $table->dropUnique(['phone']);
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropColumn(['phone', 'phone_verified_at', 'role', 'status', 'avatar_path', 'last_login_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
