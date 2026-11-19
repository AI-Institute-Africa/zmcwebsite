<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Duplicate application flags
        Schema::create('duplicate_application_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Type of duplication detected
            $table->enum('duplicate_type', [
                'same_id_different_name',
                'same_passport_different_name',
                'same_photo',
                'same_phone_email',
                'same_document_hash',
                'same_certificate',
                'same_employment_letter',
                'same_media_samples',
                'same_company_name',
                'near_duplicate_company_name',
                'director_appears_elsewhere',
                'suspicious_pattern'
            ]);
            
            // Details of the duplicate/related applications
            $table->json('related_applications'); // Array of {application_id, similarity_score, details}
            
            // Similarity metrics
            $table->unsignedTinyInteger('similarity_score')->default(0); // 0-100
            
            // Specific data that matched
            $table->string('matching_field')->nullable(); // national_id, passport, photo_hash, etc.
            $table->text('matching_value')->nullable();
            
            // Other suspected duplicates
            $table->json('other_suspected_matches')->nullable();
            
            // Flag severity
            $table->enum('flag_severity', ['low', 'medium', 'high', 'critical']);
            
            // Officer review
            $table->boolean('flagged_for_review')->default(true);
            $table->string('reviewed_by')->nullable();
            $table->text('officer_notes')->nullable();
            $table->enum('officer_determination', ['approved', 'rejected', 'pending', 'needs_investigation'])->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->index('application_id');
            $table->index('duplicate_type');
            $table->index('flag_severity');
            $table->index('similarity_score');
        });

        // Fraud risk scores
        Schema::create('fraud_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Overall risk score
            $table->unsignedTinyInteger('overall_score')->default(0); // 0-100
            
            // Score breakdown by category
            $table->unsignedTinyInteger('identity_risk_score')->default(0);
            $table->unsignedTinyInteger('document_risk_score')->default(0);
            $table->unsignedTinyInteger('duplicate_risk_score')->default(0);
            $table->unsignedTinyInteger('employment_risk_score')->default(0);
            $table->unsignedTinyInteger('company_risk_score')->default(0);
            $table->unsignedTinyInteger('payment_risk_score')->default(0);
            $table->unsignedTinyInteger('insider_risk_score')->default(0);
            
            // Risk factors contributing to score
            $table->json('risk_factors'); // Array of { factor, weight, value }
            
            // Risk level classification
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical']);
            
            // Routing decision
            $table->enum('routing_decision', [
                'auto_approve',
                'normal_review',
                'officer_review',
                'senior_review',
                'block_for_investigation',
                'pending_clarification'
            ]);
            
            // Flags set
            $table->json('flags'); // Array of specific fraud flags
            
            // Officer action
            $table->string('action_taken_by')->nullable();
            $table->enum('action_taken', ['approved', 'rejected', 'pending', 'escalated', 'archived'])->nullable();
            $table->text('action_notes')->nullable();
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('overall_score');
            $table->index('risk_level');
            $table->index('routing_decision');
        });

        // Identity verification cache
        Schema::create('identity_verification_cache', function (Blueprint $table) {
            $table->id();
            
            // Identity keys
            $table->string('id_type'); // national_id, passport, company_id
            $table->string('id_value');
            
            // Cached verification result
            $table->enum('verification_status', ['valid', 'invalid', 'suspicious', 'unknown']);
            $table->text('verification_details')->nullable();
            
            // Applications using this identity
            $table->json('applications_count'); // Count by status
            
            // Cross-checks
            $table->json('linked_identities')->nullable(); // Other IDs/passports linked to this identity
            
            // Expiration
            $table->timestamp('cached_at');
            $table->timestamp('expires_at');
            $table->boolean('is_expired')->default(false);
            
            $table->index(['id_type', 'id_value']);
            $table->index('verification_status');
            $table->index('is_expired');
        });

        // Reused document tracking
        Schema::create('reused_document_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('application_documents')->cascadeOnDelete();
            
            // Document identification
            $table->string('document_type');
            $table->string('document_hash');
            
            // Usage tracking
            $table->unsignedInteger('usage_count')->default(1);
            $table->json('used_by_applications'); // Array of {app_id, applicant_name, status, date}
            
            // Reuse pattern analysis
            $table->boolean('suspicious_reuse_detected')->default(false);
            $table->text('reuse_pattern')->nullable(); // e.g., "used by 5 different practitioners", "rapid repeated use"
            
            // Related users
            $table->json('users_involved')->nullable(); // Uploading user IDs
            $table->boolean('same_user_multiple_apps')->default(false);
            $table->boolean('different_users_same_document')->default(false);
            
            // Risk assessment
            $table->enum('reuse_risk_level', ['low', 'medium', 'high', 'critical']);
            
            // Fraud indicators
            $table->json('fraud_indicators')->nullable(); // What suggests fraud
            
            // Officer review
            $table->boolean('flagged_for_review')->default(true);
            $table->string('reviewed_by')->nullable();
            $table->text('officer_decision')->nullable();
            
            $table->timestamps();
            $table->index('document_hash');
            $table->index('suspicious_reuse_detected');
            $table->index('reuse_risk_level');
        });

        // Document reuse patterns (meta-analysis)
        Schema::create('document_reuse_patterns', function (Blueprint $table) {
            $table->id();
            
            // Pattern identification
            $table->string('pattern_type'); // certificate_reuse, employment_letter_template, photo_batch, etc.
            $table->unsignedInteger('document_count'); // How many documents in this pattern
            $table->unsignedInteger('application_count'); // How many applications affected
            
            // Details
            $table->json('documents_involved'); // Document IDs & hashes
            $table->json('applications_involved'); // Application IDs
            $table->json('applicants_involved'); // Names and details
            
            // Risk analysis
            $table->enum('pattern_risk_level', ['low', 'medium', 'high', 'critical']);
            $table->text('risk_reason')->nullable();
            
            // Fraud likelihood
            $table->unsignedTinyInteger('fraud_probability')->default(0); // 0-100
            
            // Investigation status
            $table->enum('investigation_status', ['detected', 'pending_review', 'under_investigation', 'resolved', 'closed']);
            $table->string('assigned_to')->nullable();
            $table->text('investigation_notes')->nullable();
            
            // Timestamp
            $table->timestamp('pattern_detected_at');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index('pattern_type');
            $table->index('pattern_risk_level');
            $table->index('fraud_probability');
            $table->index('investigation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_application_flags');
        Schema::dropIfExists('fraud_risk_scores');
        Schema::dropIfExists('identity_verification_cache');
        Schema::dropIfExists('reused_document_tracking');
        Schema::dropIfExists('document_reuse_patterns');
    }
};
