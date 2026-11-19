<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One person asking for one listing. The donor accepts or declines;
 * an accepted reservation ends as completed (handover done) or cancelled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('medicine_request_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 20)->default('pending')->index(); // pending | accepted | declined | cancelled | completed
            $table->string('message', 300)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('decided_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['medicine_listing_id', 'status']);
            $table->index(['requester_id', 'status']);
        });

        Schema::create('saved_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_listing_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'medicine_listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_listings');
        Schema::dropIfExists('reservations');
    }
};
