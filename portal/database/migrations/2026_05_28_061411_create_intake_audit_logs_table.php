<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Action performed: started_check, completed_check, flagged, auto_approved, correction_required, etc.
            $table->string('action');
            
            // Previous application status
            $table->string('old_status')->nullable();
            
            // New application status after action
            $table->string('new_status')->nullable();
            
            // Rules applied during this action (JSON array)
            $table->json('rules_applied')->nullable();
            
            // Confidence scores for all checks (JSON object)
            $table->json('confidence_scores')->nullable();
            
            // Document IDs that were processed (JSON array)
            $table->json('document_ids_checked')->nullable();
            
            // Human-readable reason/explanation
            $table->text('reason')->nullable();
            
            // System user ID who performed the action (usually the system user)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for querying
            $table->index(['application_id', 'action']);
            $table->index('action');
            $table->index('created_at');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_audit_logs');
    }
};
