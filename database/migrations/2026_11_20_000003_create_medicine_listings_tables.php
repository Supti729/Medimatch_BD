<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medicines offered by users. The status only changes through App\Enums\ListingStatus transitions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->foreignId('medicine_alias_id')->nullable()->constrained()->nullOnDelete();

            $table->string('brand_name', 100);
            $table->unsignedSmallInteger('quantity');
            $table->string('unit', 30);
            $table->date('expiry_date'); // stored as the first day of the expiry month
            $table->string('batch_number', 50)->nullable();
            $table->string('packaging', 20); // sealed_box | sealed_strip
            $table->string('type', 20)->default('donation'); // donation | affordable_access

            $table->string('status', 20)->default('draft')->index();
            $table->text('review_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->foreignId('division_id')->constrained();
            $table->foreignId('district_id')->constrained();
            $table->foreignId('upazila_id')->constrained();

            $table->timestamps();

            $table->index(['status', 'district_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('medicine_listings');
    }
};
