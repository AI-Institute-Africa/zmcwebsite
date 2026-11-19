<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add source to distinguish between gateway and manual/offline
            if (!Schema::hasColumn('payments', 'source')) {
                $table->string('source')->default('gateway')->after('method'); // gateway, offline
            }

            // Link to payer
            if (!Schema::hasColumn('payments', 'payer_user_id')) {
                $table->unsignedBigInteger('payer_user_id')->nullable()->after('application_id');
            }
            if (!Schema::hasColumn('payments', 'media_house_id')) {
                $table->unsignedBigInteger('media_house_id')->nullable()->after('payer_user_id');
            }

            // More detailed status for reversals/refunds
            // payment_status: paid, pending, failed, reversed, refunded, partially_refunded

            // Additional fields for offline payments
            if (!Schema::hasColumn('payments', 'bank_name')) {
                $table->string('bank_name')->nullable();
            }
            if (!Schema::hasColumn('payments', 'deposit_slip_ref')) {
                $table->string('deposit_slip_ref')->nullable();
            }
            if (!Schema::hasColumn('payments', 'proof_file_path')) {
                $table->string('proof_file_path')->nullable();
            }

            // Gateway specific
            if (!Schema::hasColumn('payments', 'gateway_response')) {
                $table->json('gateway_response')->nullable();
            }
            if (!Schema::hasColumn('payments', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable();
            }

            // Reversal/Refund info
            if (!Schema::hasColumn('payments', 'parent_payment_id')) {
                $table->unsignedBigInteger('parent_payment_id')->nullable();
            }
            if (!Schema::hasColumn('payments', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable();
            }

            // Reconciliation
            if (!Schema::hasColumn('payments', 'reconciled')) {
                $table->boolean('reconciled')->default(false);
            }
            if (!Schema::hasColumn('payments', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable();
            }
            if (!Schema::hasColumn('payments', 'reconciled_by')) {
                $table->unsignedBigInteger('reconciled_by')->nullable();
            }

            // Metadata for ledger filtering
            if (!Schema::hasColumn('payments', 'applicant_category')) {
                $table->string('applicant_category')->nullable(); // journalist, media_practitioner, media_house
            }
            if (!Schema::hasColumn('payments', 'residency')) {
                $table->string('residency')->nullable(); // local, foreign
            }
            if (!Schema::hasColumn('payments', 'service_type')) {
                $table->string('service_type')->nullable(); // new_accreditation, etc.
            }
        });

        Schema::create('payment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action'); // created, status_change, reversed, refunded, reconciled
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('comment')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->index();
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->unsignedBigInteger('processed_by');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamps();
        });

        Schema::create('receipt_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 10)->default('RCT');
            $table->string('year', 4);
            $table->integer('last_number')->default(0);
            $table->unique(['prefix', 'year']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_sequences');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_audit_logs');
        Schema::table('payments', function (Blueprint $table) {
             $table->dropColumn([
                'source', 'payer_user_id', 'media_house_id', 'bank_name', 'deposit_slip_ref',
                'proof_file_path', 'gateway_response', 'last_checked_at', 'parent_payment_id',
                'reversal_reason', 'reconciled', 'reconciled_at', 'reconciled_by',
                'applicant_category', 'residency', 'service_type'
             ]);
        });
    }
};
