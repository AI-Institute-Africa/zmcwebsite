<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {

            // Applicant uploads
            if (!Schema::hasColumn('applications', 'payment_proof_path')) {
                $table->string('payment_proof_path')->nullable()->after('waiver_path');
            }
            if (!Schema::hasColumn('applications', 'payment_proof_uploaded_at')) {
                $table->timestamp('payment_proof_uploaded_at')->nullable()->after('payment_proof_path');
            }

            // Waiver approval
            if (!Schema::hasColumn('applications', 'waiver_status')) {
                $table->enum('waiver_status', ['none', 'submitted', 'approved', 'rejected'])
                    ->default('none')
                    ->after('waiver_path');
            }
            if (!Schema::hasColumn('applications', 'waiver_reviewed_by')) {
                $table->unsignedBigInteger('waiver_reviewed_by')->nullable()->after('waiver_status');
            }
            if (!Schema::hasColumn('applications', 'waiver_reviewed_at')) {
                $table->timestamp('waiver_reviewed_at')->nullable()->after('waiver_reviewed_by');
            }
            if (!Schema::hasColumn('applications', 'waiver_review_notes')) {
                $table->text('waiver_review_notes')->nullable()->after('waiver_reviewed_at');
            }

            // Proof approval
            if (!Schema::hasColumn('applications', 'proof_status')) {
                $table->enum('proof_status', ['none', 'submitted', 'approved', 'rejected'])
                    ->default('none')
                    ->after('payment_proof_uploaded_at');
            }
            if (!Schema::hasColumn('applications', 'proof_reviewed_by')) {
                $table->unsignedBigInteger('proof_reviewed_by')->nullable()->after('proof_status');
            }
            if (!Schema::hasColumn('applications', 'proof_reviewed_at')) {
                $table->timestamp('proof_reviewed_at')->nullable()->after('proof_reviewed_by');
            }
            if (!Schema::hasColumn('applications', 'proof_review_notes')) {
                $table->text('proof_review_notes')->nullable()->after('proof_reviewed_at');
            }

            // Paynow status
            if (!Schema::hasColumn('applications', 'paynow_poll_url')) {
                $table->string('paynow_poll_url')->nullable()->after('paynow_reference');
            }
            if (!Schema::hasColumn('applications', 'paynow_confirmed_at')) {
                $table->timestamp('paynow_confirmed_at')->nullable()->after('paynow_poll_url');
            }

            // Idempotency key for webhook processing
            if (!Schema::hasColumn('applications', 'paynow_webhook_last_hash')) {
                $table->string('paynow_webhook_last_hash', 128)->nullable()->after('paynow_confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $cols = [
                'payment_proof_path',
                'payment_proof_uploaded_at',
                'waiver_status',
                'waiver_reviewed_by',
                'waiver_reviewed_at',
                'waiver_review_notes',
                'proof_status',
                'proof_reviewed_by',
                'proof_reviewed_at',
                'proof_review_notes',
                'paynow_poll_url',
                'paynow_confirmed_at',
                'paynow_webhook_last_hash',
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('applications', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
