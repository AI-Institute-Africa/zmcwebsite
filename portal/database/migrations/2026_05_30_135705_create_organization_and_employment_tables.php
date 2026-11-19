<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Employer verification - track registered employers
        Schema::create('employer_verification', function (Blueprint $table) {
            $table->id();
            
            // Organization identification
            $table->string('employer_name');
            $table->string('employer_type'); // media_house, news_agency, freelance, foreign_service, etc.
            
            // Registration status in system
            $table->enum('registration_status', ['registered', 'pending_registration', 'unknown', 'unverified', 'rejected']);
            
            // Registration/application reference
            $table->foreignId('registered_application_id')->nullable()->constrained('applications')->nullOnDelete();
            
            // Contact information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('physical_address')->nullable();
            
            // Company details
            $table->string('company_registration_number')->nullable();
            $table->json('directors')->nullable();
            
            // Verification details
            $table->text('verification_notes')->nullable();
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // High-risk flags
            $table->boolean('is_high_risk')->default(false);
            $table->json('risk_flags')->nullable();
            
            // Usage tracking
            $table->unsignedInteger('employment_letters_issued')->default(0);
            $table->unsignedInteger('practitioners_verified')->default(0);
            $table->json('practitioners_list')->nullable();
            
            $table->timestamps();
            
            // Indexes with short names
            $table->index('employer_name', 'emp_ver_name_idx');
            $table->index('registration_status', 'emp_ver_status_idx');
            $table->index('is_high_risk', 'emp_ver_risk_idx');
        });

        // Employment letter signatures - track letterhead signatories
        Schema::create('employment_letter_signatures', function (Blueprint $table) {
            $table->id();
            
            // Signatory information
            $table->string('signatory_name');
            $table->string('signatory_position');
            $table->string('employer_name');
            $table->foreignId('employer_verification_id')->constrained('employer_verification')->cascadeOnDelete();
            
            // Signature/stamp patterns
            $table->string('signature_hash')->nullable();
            $table->string('stamp_hash')->nullable();
            
            // Usage statistics
            $table->unsignedInteger('letters_signed')->default(1);
            $table->json('letters_signed_ids')->nullable(); // Array of application IDs
            
            // Associated employment letters
            $table->json('employment_letters')->nullable(); // Document hashes/IDs
            
            // Verification
            $table->boolean('signature_verified')->default(false);
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Risk detection
            $table->boolean('suspicious_activity_detected')->default(false);
            $table->unsignedInteger('letter_reuse_count')->default(0);
            $table->boolean('signs_too_many_letters')->default(false); // Threshold exceeded
            $table->json('suspicious_patterns')->nullable();
            
            $table->timestamps();
            
            // Indexes with short names
            $table->index('employer_name', 'emp_sig_employer_idx');
            $table->index('signatory_name', 'emp_sig_signatory_idx');
            $table->index('suspicious_activity_detected', 'emp_sig_suspicious_idx');
        });

        // Organization name mapping - trading vs registered names
        Schema::create('organization_name_mapping', function (Blueprint $table) {
            $table->id();
            
            // Names
            $table->string('registered_name');
            $table->string('trading_name')->nullable();
            $table->json('alternative_names')->nullable(); // Array of other names used
            
            // Application reference
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Company information
            $table->string('company_registration_number')->nullable();
            $table->text('company_details')->nullable();
            
            // Proof of name difference
            $table->foreignId('proof_document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            $table->text('proof_description')->nullable();
            
            // Verification
            $table->boolean('name_difference_verified')->default(false);
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Official record
            $table->boolean('is_official_mapping')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes with short names
            $table->index(['registered_name', 'trading_name'], 'org_name_mapping_names_idx');
            $table->index('application_id', 'org_name_mapping_app_idx');
        });

        // Director cross-reference - track directors across organizations
        Schema::create('director_cross_reference', function (Blueprint $table) {
            $table->id();
            
            // Director identification
            $table->string('director_name');
            $table->string('director_id_type'); // national_id, passport
            $table->string('director_id_number');
            
            // Appearances
            $table->json('organizations')->nullable(); // Array of organizations where director sits
            $table->json('applications')->nullable(); // Array of applications involving this director
            $table->unsignedInteger('organization_count')->default(1);
            
            // Details
            $table->string('nationality')->nullable();
            $table->date('date_of_birth')->nullable();
            
            // Cross-holding tracking
            $table->boolean('holds_multiple_directorships')->default(false);
            $table->json('directorship_list')->nullable();
            
            // Compliance checks
            $table->boolean('disclosed_in_all_applications')->default(false);
            $table->json('missing_disclosures')->nullable(); // Applications where not disclosed
            
            // Risk assessment
            $table->boolean('flagged_for_review')->default(false);
            $table->text('risk_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes with short names - THIS WAS THE PROBLEM
            $table->index(['director_id_type', 'director_id_number'], 'dir_cross_ref_type_number_idx');
            $table->index('director_name', 'dir_cross_ref_name_idx');
            $table->index('holds_multiple_directorships', 'dir_cross_ref_multiple_idx');
            $table->index('flagged_for_review', 'dir_cross_ref_flagged_idx');
        });

        // Sensitive action approvals
        Schema::create('sensitive_action_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Action details
            $table->enum('action_type', [
                'override_hard_stop',
                'override_fraud_flag',
                'bypass_payment_verification',
                'approval_without_full_validation',
                'data_modification_post_submission',
                'director_approval_direct',
                'fee_waiver',
                'other_sensitive'
            ]);
            
            // Performer (maker)
            $table->string('action_initiated_by');
            $table->string('initiator_role');
            $table->text('action_description');
            $table->json('action_details');
            
            // Reason
            $table->text('justification');
            $table->json('supporting_evidence')->nullable();
            
            // Approval chain
            $table->boolean('requires_approval')->default(true);
            $table->string('required_approver_role');
            $table->unsignedInteger('approval_count_required')->default(1);
            
            // Approvals (checker)
            $table->json('approvals'); // Array of {approved_by, role, timestamp, notes}
            $table->unsignedInteger('approvals_received')->default(0);
            $table->boolean('all_approvals_received')->default(false);
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled']);
            
            // Audit
            $table->timestamp('initiated_at');
            $table->timestamp('decision_made_at')->nullable();
            
            $table->timestamps();
            
            // Indexes with short names
            $table->index('application_id', 'sens_app_app_idx');
            $table->index('action_type', 'sens_app_action_idx');
            $table->index('status', 'sens_app_status_idx');
            $table->index('action_initiated_by', 'sens_app_initiator_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_verification');
        Schema::dropIfExists('employment_letter_signatures');
        Schema::dropIfExists('organization_name_mapping');
        Schema::dropIfExists('director_cross_reference');
        Schema::dropIfExists('sensitive_action_approvals');
    }
};