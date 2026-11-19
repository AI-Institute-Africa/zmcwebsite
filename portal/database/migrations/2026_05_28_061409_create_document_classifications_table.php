<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('application_documents')->cascadeOnDelete();
            
            // Expected document type as uploaded (local_national_id, passport, etc.)
            $table->string('expected_document_type');
            
            // Detected document type from OCR/classification
            $table->string('detected_document_type')->nullable();
            
            // Confidence score (0-100) for the classification
            $table->unsignedTinyInteger('confidence_score')->default(0);
            
            // OCR extracted text (truncated excerpt)
            $table->longText('extracted_text_excerpt')->nullable();
            
            // Keywords found (JSON array)
            $table->json('matched_keywords')->nullable();
            
            // Keywords that should have been found but weren't (JSON array)
            $table->json('missing_keywords')->nullable();
            
            // Warnings: quality issues, unclear text, etc. (JSON array)
            $table->json('warnings')->nullable();
            
            // Blocking errors: corrupted, unreadable, wrong type, etc. (JSON array)
            $table->json('blocking_errors')->nullable();
            
            // Recommendation: pass, manual_review, or fail
            $table->enum('recommendation', ['pass', 'manual_review', 'fail']);
            
            // Additional details as JSON
            $table->json('details')->nullable();
            
            $table->timestamps();
            
            // Indexes with short names
            $table->index(['application_id', 'expected_document_type'], 'doc_class_app_expected_idx');
            $table->index('recommendation', 'doc_class_recommendation_idx');
            $table->index('confidence_score', 'doc_class_confidence_idx');
            $table->index('created_at', 'doc_class_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_classifications');
    }
};