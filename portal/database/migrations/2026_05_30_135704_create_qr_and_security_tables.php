<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Accreditation numbers - auto-generated
        Schema::create('accreditation_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Accreditation number
            $table->string('accreditation_number')->unique();
            $table->string('format'); // DDMMCCSSYY pattern explanation
            
            // Components
            $table->date('issue_date');
            $table->year('issue_year');
            $table->string('category_code');
            $table->unsignedInteger('sequence_number');
            
            // Status
            $table->enum('status', ['active', 'expired', 'replaced', 'suspended', 'revoked', 'cancelled']);
            
            // Validity period
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            
            // History
            $table->string('replaced_by')->nullable(); // Accreditation number if replaced
            $table->string('replaces')->nullable(); // Accreditation number it replaced
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Holder info (denormalized for quick lookup)
            $table->string('holder_name');
            $table->string('holder_id_type'); // national_id, passport
            $table->string('holder_id_number');
            $table->string('applicant_category');
            
            // QR code reference
            $table->string('qr_code_id')->nullable();
            
            $table->timestamps();
            $table->index('accreditation_number');
            $table->index('status');
            $table->index('holder_id_number');
            $table->index('valid_until');
        });

        // Registration numbers - auto-generated for media houses
        Schema::create('registration_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Registration number
            $table->string('registration_number')->unique();
            $table->string('format'); // DDMMCCSSYY pattern explanation
            
            // Components
            $table->date('issue_date');
            $table->year('issue_year');
            $table->string('category_code');
            $table->unsignedInteger('sequence_number');
            
            // Status
            $table->enum('status', ['active', 'expired', 'replaced', 'suspended', 'revoked', 'cancelled']);
            
            // Validity period
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            
            // History
            $table->string('replaced_by')->nullable();
            $table->string('replaces')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Organization info (denormalized)
            $table->string('organization_name');
            $table->string('organization_type');
            
            // QR code reference
            $table->string('qr_code_id')->nullable();
            
            $table->timestamps();
            $table->index('registration_number');
            $table->index('status');
            $table->index('organization_name');
            $table->index('valid_until');
        });

        // QR code registry
        Schema::create('qr_code_registry', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code_id')->unique();
            
            // What it points to
            $table->string('credential_type'); // accreditation or registration
            $table->string('credential_number');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // QR data
            $table->text('qr_data'); // The QR code content (signed token)
            $table->string('qr_signature'); // Signature to verify QR hasn't been tampered
            $table->string('qr_public_key_id'); // Which key signed this QR
            
            // Security
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_valid')->default(true);
            
            // Status tracking
            $table->enum('current_status', ['active', 'expired', 'replaced', 'revoked', 'suspended']);
            $table->string('status_reason')->nullable();
            
            // Printing
            $table->boolean('printed')->default(false);
            $table->string('printed_by')->nullable();
            $table->timestamp('printed_at')->nullable();
            
            // Collected
            $table->boolean('collected')->default(false);
            $table->timestamp('collected_at')->nullable();
            
            // Photo for practitioner cards
            $table->string('photo_url')->nullable();
            
            $table->timestamps();
            $table->index('qr_code_id');
            $table->index('credential_number');
            $table->index('application_id');
            $table->index('current_status');
        });

        // QR scan audit log
        Schema::create('qr_scan_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code_id');
            $table->foreign('qr_code_id')->references('qr_code_id')->on('qr_code_registry')->cascadeOnDelete();
            
            // Scan details
            $table->string('credential_number');
            $table->enum('credential_type', ['accreditation', 'registration']);
            
            // Scanner information
            $table->string('scanner_ip_address')->nullable();
            $table->string('scanner_device')->nullable();
            $table->string('scanner_location')->nullable();
            
            // What was shown to scanner
            $table->string('holder_name');
            $table->string('holder_category')->nullable();
            $table->enum('current_status', ['active', 'expired', 'replaced', 'suspended', 'revoked']);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('photo_url')->nullable();
            
            // Verification result
            $table->boolean('qr_signature_valid')->default(true);
            $table->boolean('credential_exists')->default(true);
            $table->boolean('credential_active')->default(true);
            
            // Flags if QR data doesn't match credential
            $table->json('verification_warnings')->nullable();
            
            // Timestamp
            $table->timestamp('scanned_at');
            $table->timestamps();
            
            $table->index('qr_code_id');
            $table->index('credential_number');
            $table->index('scanned_at');
            $table->index('current_status');
        });

        // Officer overrides
        Schema::create('officer_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // What was overridden
            $table->enum('override_type', [
                'hard_stop_error_bypassed',
                'soft_warning_ignored',
                'manual_review_skipped',
                'fraud_flag_ignored',
                'duplicate_ignored',
                'payment_requirement_waived',
                'document_quality_issue_accepted',
                'other'
            ]);
            
            // Specific item overridden
            $table->string('item_id')->nullable(); // error_code, warning_code, fraud_flag_id, etc.
            $table->text('item_description')->nullable();
            
            // Override reason
            $table->enum('reason_category', [
                'legal_justification',
                'hardship',
                'government_directive',
                'documented_appeal',
                'correction_provided',
                'administrative_error',
                'special_circumstances',
                'other'
            ]);
            $table->text('detailed_reason')->required();
            
            // Supporting documentation
            $table->json('supporting_documents')->nullable();
            
            // Authorization
            $table->string('overridden_by');
            $table->string('overridden_by_role');
            $table->timestamp('overridden_at');
            
            // Approval chain
            $table->boolean('requires_second_approval')->default(true);
            $table->string('approved_by')->nullable();
            $table->string('approved_by_role')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approver_notes')->nullable();
            
            // Status
            $table->enum('override_status', ['pending_approval', 'approved', 'rejected']);
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('override_type');
            $table->index('overridden_by');
            $table->index('override_status');
        });

        // Override authorizations - track who can approve overrides
        Schema::create('override_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('user_role');
            
            // Permissions
            $table->json('can_override_types'); // Array of override types this user can approve
            $table->json('approval_level'); // e.g., ['soft_warning', 'manual_review'] vs ['all_errors']
            
            // Limits
            $table->unsignedInteger('daily_override_limit')->nullable();
            $table->unsignedInteger('monthly_override_limit')->nullable();
            
            // Activation
            $table->timestamp('authorized_from');
            $table->timestamp('authorized_until')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->string('authorized_by');
            $table->text('authorization_reason')->nullable();
            
            $table->timestamps();
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditation_numbers');
        Schema::dropIfExists('registration_numbers');
        Schema::dropIfExists('qr_code_registry');
        Schema::dropIfExists('qr_scan_audit_log');
        Schema::dropIfExists('officer_overrides');
        Schema::dropIfExists('override_authorizations');
    }
};
