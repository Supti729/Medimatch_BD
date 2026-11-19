<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The verified medicine catalogue.
 *
 * medicines         one row per medicine identity: generic name + strength + dosage form
 * medicine_aliases  brand names for that identity (Napa, Ace ... for Paracetamol 500 mg tablet)
 *
 * Listings must point to a catalogue medicine, so strength and form are never guessed from free text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('generic_name', 150);
            $table->string('strength', 50);
            $table->string('dosage_form', 30);
            $table->boolean('requires_prescription')->default(false);
            $table->string('eligibility', 20)->default('allowed')->index(); // allowed | not_allowed
            $table->string('restriction_reason', 200)->nullable();
            $table->timestamps();

            $table->unique(['generic_name', 'strength', 'dosage_form']);
            $table->index('generic_name');
        });

        Schema::create('medicine_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->string('brand_name', 100);
            $table->string('manufacturer', 150)->nullable();
            $table->timestamps();

            $table->unique(['medicine_id', 'brand_name']);
            $table->index('brand_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_aliases');
        Schema::dropIfExists('medicines');
    }
};
