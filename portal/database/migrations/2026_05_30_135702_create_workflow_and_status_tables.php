<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Application validation state
        Schema::create('application_validation_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Current validation phase
            $table->enum('validation_phase', [
                'document_intake',
                'identity_verification',
                'document_matching',
                'duplicate_check',
                'employment_verification',
                'company_verification',
                'payment_verification',
                'fraud_risk_assessment',
                'ready_for_review',
                'approved',
                'rejected',
                'archived'
            ]);
            
            // What validation has been completed
            $table->json('completed_validations'); // Array of completed validation types
            $table->json('pending_validations'); // Array of pending validation types
            $table->json('failed_validations'); // Array of failed validations with reasons
            
            // Hard-stop errors (blocking)
            $table->json('hard_stop_errors')->nullable(); // Cannot proceed until resolved
            $table->unsignedInteger('hard_stop_count')->default(0);
            
            // Soft warnings (allow continuation but flagged)
            $table->json('soft_warnings')->nullable(); // Allow continuation but flag for review
            $table->unsignedInteger('soft_warning_count')->default(0);
            
            // Manual review items
            $table->json('manual_review_items')->nullable(); // Require officer discretion
            $table->unsignedInteger('manual_review_count')->default(0);
            
            // Validation progress
            $table->unsignedTinyInteger('overall_progress')->default(0); // 0-100%
            
            // Last validation run
            $table->timestamp('last_validation_run')->nullable();
            $table->unsignedInteger('validation_run_count')->default(0);
            
            // Applicant notification
            $table->boolean('applicant_notified_of_errors')->default(false);
            $table->timestamp('last_notification')->nullable();
            
            // Officer notes
            $table->text('internal_notes')->nullable();
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('validation_phase');
            $table->index('hard_stop_count');
            $table->index('soft_warning_count');
        });

        // Validation rules engine - configurable rules
        Schema::create('validation_rules_engine', function (Blueprint $table) {
            $table->id();
            
            // Rule identification
            $table->string('rule_code')->unique(); // e.g., LOCAL_ID_NAME_MATCH, EMPLOYMENT_ORG_MATCH
            $table->string('rule_name');
            $table->text('rule_description');
            
            // Rule configuration
            $table->enum('rule_type', ['hard_stop', 'soft_warning', 'manual_review']);
            $table->string('applies_to'); // Which application types: local_practitioner, foreign_practitioner, etc.
            
            // Rule logic
            $table->text('condition'); // JSON representation of condition
            $table->text('error_message'); // Message for applicant if fails
            $table->text('officer_flag'); // Flag for officer if triggered
            
            // Rule parameters
            $table->json('parameters')->nullable(); // Configurable thresholds, e.g., {name_match_threshold: 90}
            
            // Rule status
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            
            // Audit
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->text('change_log')->nullable();
            
            $table->timestamps();
            $table->index('rule_code');
            $table->index('rule_type');
            $table->index('is_active');
        });

        // Hard-stop errors
        Schema::create('hard_stop_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Error identification
            $table->string('error_code'); // NATIONAL_ID_NAME_MISMATCH, ID_NUMBER_MISMATCH, etc.
            $table->text('error_message'); // Applicant-facing message
            $table->text('technical_details'); // For officers
            
            // Error severity
            $table->enum('severity', ['critical', 'high']);
            
            // Which field/document caused the error
            $table->string('field_name')->nullable();
            $table->foreignId('document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            
            // Expected vs actual values
            $table->text('expected_value')->nullable();
            $table->text('actual_value')->nullable();
            
            // Blocking status
            $table->boolean('is_blocking_submission')->default(true);
            $table->boolean('is_blocking_approval')->default(true);
            
            // Resolution tracking
            $table->enum('status', ['new', 'notified_to_applicant', 'corrections_submitted', 'resolved', 'waived']);
            $table->string('resolved_by')->nullable();
            $table->text('resolution_reason')->nullable();
            $table->timestamp('resolved_at')->nullable();
            
            // Waiver information (if applicable)
            $table->string('waiver_approved_by')->nullable();
            $table->text('waiver_reason')->nullable();
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('error_code');
            $table->index('status');
            $table->index('is_blocking_submission');
        });

        // Soft warnings
        Schema::create('soft_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Warning identification
            $table->string('warning_code'); // LOW_OCR_CONFIDENCE, NAME_REORDERING, MISSING_MIDDLENAME, etc.
            $table->text('warning_message'); // For applicant: gentle guidance
            $table->text('technical_details'); // For officers: why flagged
            
            // Warning severity
            $table->enum('severity', ['low', 'medium']);
            
            // Related field/document
            $table->string('field_name')->nullable();
            $table->foreignId('document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            
            // Values involved
            $table->text('value_1')->nullable();
            $table->text('value_2')->nullable();
            $table->unsignedTinyInteger('similarity_score')->nullable(); // 0-100
            
            // Applicant action
            $table->boolean('applicant_acknowledged')->default(false);
            $table->text('applicant_explanation')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            
            // Officer action
            $table->boolean('flagged_for_officer_review')->default(true);
            $table->string('reviewed_by')->nullable();
            $table->enum('officer_determination', ['approved', 'requires_correction', 'pending'])->nullable();
            $table->text('officer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('warning_code');
            $table->index('severity');
            $table->index('flagged_for_officer_review');
        });

        // Application validation events (audit trail)
        Schema::create('validation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Event type
            $table->enum('event_type', [
                'validation_started',
                'validation_completed',
                'document_uploaded',
                'document_reprocessed',
                'hard_stop_error_created',
                'hard_stop_error_resolved',
                'soft_warning_created',
                'manual_review_flagged',
                'applicant_corrected_error',
                'officer_reviewed_application',
                'officer_overrode_validation',
                'application_auto_approved',
                'application_submitted_for_review'
            ]);
            
            // Event details
            $table->text('event_description');
            $table->json('event_data')->nullable(); // Additional context
            
            // Performer
            $table->string('performed_by')->nullable(); // User ID, or 'system'
            $table->enum('performer_type', ['applicant', 'officer', 'system', 'admin']);
            
            // Related items
            $table->foreignId('document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            $table->string('related_error_code')->nullable();
            $table->string('related_warning_code')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->index('application_id');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_validation_state');
        Schema::dropIfExists('validation_rules_engine');
        Schema::dropIfExists('hard_stop_errors');
        Schema::dropIfExists('soft_warnings');
        Schema::dropIfExists('validation_events');
    }
};
