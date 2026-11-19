<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medicines that users need. A request points to a catalogue medicine,
 * so matching compares the same medicine identity (generic name + strength + form).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();

            $table->unsignedSmallInteger('quantity');
            $table->string('unit', 30);
            $table->string('preference', 20)->default('either'); // either | donation | affordable_access
            $table->string('notes', 500)->nullable();

            $table->string('status', 30)->default('active')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();

            $table->foreignId('division_id')->constrained();
            $table->foreignId('district_id')->constrained();
            $table->foreignId('upazila_id')->constrained();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['medicine_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_requests');
    }
};
