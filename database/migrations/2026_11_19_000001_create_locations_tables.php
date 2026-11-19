<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bangladesh administrative areas: 8 divisions, 64 districts, 495 upazilas.
 * Filled by LocationSeeder from database/data/bangladesh-locations.json.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('bn_name', 100);
            $table->string('code', 20)->unique(); // Bangladesh Bureau of Statistics (BBS) code
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('bn_name', 100);
            $table->string('code', 20)->unique();
            $table->unique(['division_id', 'name']);
        });

        Schema::create('upazilas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('bn_name', 150);
            $table->string('code', 20)->unique();
            $table->unique(['district_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upazilas');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('divisions');
    }
};
