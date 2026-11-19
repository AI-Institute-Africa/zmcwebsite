<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            
            // Overall score (0-100) calculated as weighted average
            $table->unsignedTinyInteger('overall_score')->default(0);
            
            // Final decision: auto_pass, flagged_for_review, correction_needed, blocked
            $table->enum('decision', ['auto_pass', 'flagged_for_review', 'correction_needed', 'blocked']);
            
            // Rules that were applied and their scores (JSON)
            $table->json('matched_rules')->nullable();
            
            // Individual check results and scores (JSON)
            // e.g., {document_security: 95, identity_verification: 88, payment: 92, ...}
            $table->json('check_results')->nullable();
            
            // Recommended application category
            $table->string('category_recommendation')->nullable();
            
            // Category assignment confidence score (0-100)
            $table->unsignedTinyInteger('category_confidence_score')->default(0);
            
            // JSON reason object: {decision: "reason_code", details: "human readable text"}
            $table->json('reason')->nullable();
            
            // System user ID who performed the check
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Document IDs that were checked (JSON array)
            $table->json('document_ids_checked')->nullable();
            
            // Count of documents processed
            $table->unsignedTinyInteger('documents_checked_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('decision');
            $table->index('overall_score');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_decisions');
    }
};
