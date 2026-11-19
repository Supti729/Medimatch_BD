<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only record of administrative and sensitive actions: who did what, to what, when.
 * Rows are never updated or deleted by the application (see App\Models\AuditLog).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60)->index();          // e.g. listing.approved, verification.document_viewed
            $table->string('subject_type', 40)->nullable(); // e.g. listing, user, report
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 255);
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
