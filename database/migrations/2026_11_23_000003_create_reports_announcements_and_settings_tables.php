<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reports from users about a listing or a conversation
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type', 20);             // listing | conversation
            $table->unsignedBigInteger('target_id');
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 30);
            $table->string('details', 1000)->nullable();
            $table->string('status', 20)->default('open')->index(); // open | escalated | resolved | rejected
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolution_note', 1000)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });

        // Platform-wide announcements sent by administrators (delivered as notifications)
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 100);
            $table->string('message', 500);
            $table->string('audience', 20);
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();
        });

        // Settings administrators can change (applied over config/medimatch.php at start-up)
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->string('value', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('reports');
    }
};
