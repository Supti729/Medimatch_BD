<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prescription checks for requests of prescription medicines.
 *
 * Documents are stored on the PRIVATE disk (storage/app/private), never in a public folder.
 * Only authorized reviewers and administrators can open them, and every view is logged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_request_id')->constrained()->cascadeOnDelete();
            $table->string('document_path');
            $table->string('mime_type', 50);
            $table->unsignedInteger('size_kb');
            $table->string('status', 30)->default('submitted')->index(); // submitted | under_review | verified | rejected | reupload_required
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewer_note', 1000)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['medicine_request_id', 'status']);
        });

        // Status history shown to the user and the reviewer
        Schema::create('verification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30);
            $table->string('note', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_events');
        Schema::dropIfExists('verification_submissions');
    }
};
