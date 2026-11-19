<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Payment verification records
        Schema::create('payment_verification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Payment source
            $table->enum('payment_source', ['paynow_gateway', 'bank_transfer', 'cash_office', 'fee_waiver', 'manual_entry']);
            
            // Payment details
            $table->string('transaction_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('currency')->default('ZWL');
            
            // Payment status
            $table->enum('payment_status', [
                'pending',
                'initiated',
                'paid',
                'failed',
                'reversed',
                'under_review',
                'refunded',
                'waived',
                'partial'
            ]);
            
            // Gateway verification
            $table->string('gateway_response_code')->nullable();
            $table->text('gateway_response_message')->nullable();
            $table->json('gateway_data')->nullable();
            
            // Manual verification (for cash/bank transfers)
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Payment proof
            $table->boolean('proof_of_payment_uploaded')->default(false);
            $table->foreignId('proof_document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            
            // Dates
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('confirmation_date')->nullable();
            
            // Fee waiver information
            $table->string('fee_waiver_approved_by')->nullable();
            $table->text('fee_waiver_reason')->nullable();
            
            // Flags
            $table->boolean('is_duplicate_payment')->default(false);
            $table->boolean('amount_mismatch')->default(false);
            $table->text('mismatch_details')->nullable();
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('transaction_reference');
            $table->index('payment_status');
            $table->index('payment_date');
        });

        // Payment audit trail
        Schema::create('payment_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Event
            $table->enum('event_type', [
                'payment_initiated',
                'payment_completed',
                'payment_failed',
                'payment_verified',
                'payment_reversed',
                'fee_waived',
                'correction_made',
                'refund_processed',
                'flag_cleared'
            ]);
            
            // Details
            $table->string('transaction_reference')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();
            
            // Performer
            $table->string('performed_by');
            $table->enum('performer_type', ['system', 'applicant', 'officer', 'finance']);
            
            // Additional info
            $table->text('reason_or_note')->nullable();
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('event_type');
            $table->index('performed_by');
            $table->index('created_at');
        });

        // Levy calculations
        Schema::create('levy_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('media_house_registration_id')->nullable()->constrained('applications')->nullOnDelete();
            
            // Financial year
            $table->year('financial_year');
            
            // Turnover information
            $table->decimal('gross_annual_turnover', 15, 2);
            $table->string('currency')->default('ZWL');
            
            // Source document
            $table->foreignId('audited_financial_document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            $table->string('auditor_name')->nullable();
            $table->date('auditor_sign_off_date')->nullable();
            
            // Levy calculation
            $table->decimal('levy_percentage', 5, 3)->default(0.500); // 0.5%
            $table->decimal('levy_due', 15, 2);
            $table->decimal('levy_paid', 15, 2)->default(0);
            $table->decimal('levy_outstanding', 15, 2);
            
            // Payment status
            $table->enum('status', ['due', 'partial_paid', 'paid', 'overdue', 'disputed', 'waived']);
            
            // Validation
            $table->json('validation_issues')->nullable(); // Any discrepancies detected
            $table->enum('validation_status', ['pass', 'warning', 'failed']);
            
            // Officer review
            $table->string('reviewed_by')->nullable();
            $table->text('officer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            // Due date
            $table->date('due_date')->nullable();
            $table->boolean('is_overdue')->default(false);
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('financial_year');
            $table->index('status');
            $table->index('is_overdue');
        });

        // Fee waiver approvals
        Schema::create('fee_waiver_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Waiver details
            $table->decimal('original_fee', 10, 2);
            $table->decimal('waived_amount', 10, 2);
            $table->decimal('payable_amount', 10, 2);
            
            // Waiver type
            $table->enum('waiver_type', [
                'hardship',
                'government_employee',
                'student',
                'academic_research',
                'public_interest',
                'partial_waiver',
                'full_waiver',
                'other'
            ]);
            
            // Justification
            $table->text('justification');
            $table->json('supporting_documents')->nullable();
            
            // Approval chain
            $table->string('requested_by');
            $table->string('approved_by');
            $table->enum('approver_role', ['officer', 'registrar', 'director', 'commissioner']);
            
            // Dates
            $table->timestamp('requested_at');
            $table->timestamp('approved_at');
            
            // Authority reference
            $table->string('approval_reference')->unique();
            $table->text('authority_notes')->nullable();
            
            // Audit
            $table->json('approval_chain_audit');
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('approved_by');
            $table->index('waiver_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_verification_records');
        Schema::dropIfExists('payment_audit_trail');
        Schema::dropIfExists('levy_calculations');
        Schema::dropIfExists('fee_waiver_approvals');
    }
};
