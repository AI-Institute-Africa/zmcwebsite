<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Payment proof metadata (audit-friendly)
            if (!Schema::hasColumn('applications', 'proof_payer_first_name')) {
                $table->string('proof_payer_first_name')->nullable()->after('payment_proof_uploaded_at');
            }
            if (!Schema::hasColumn('applications', 'proof_payer_last_name')) {
                $table->string('proof_payer_last_name')->nullable()->after('proof_payer_first_name');
            }
            if (!Schema::hasColumn('applications', 'proof_payment_date')) {
                $table->date('proof_payment_date')->nullable()->after('proof_payer_last_name');
            }
            if (!Schema::hasColumn('applications', 'proof_amount_paid')) {
                $table->decimal('proof_amount_paid', 12, 2)->nullable()->after('proof_payment_date');
            }
            if (!Schema::hasColumn('applications', 'proof_bank_name')) {
                $table->string('proof_bank_name')->nullable()->after('proof_amount_paid');
            }
            if (!Schema::hasColumn('applications', 'proof_original_name')) {
                $table->string('proof_original_name')->nullable()->after('proof_bank_name');
            }
            if (!Schema::hasColumn('applications', 'proof_mime')) {
                $table->string('proof_mime', 100)->nullable()->after('proof_original_name');
            }
            if (!Schema::hasColumn('applications', 'proof_file_hash')) {
                $table->string('proof_file_hash', 64)->nullable()->after('proof_mime');
            }

            // Waiver metadata (audit-friendly)
            if (!Schema::hasColumn('applications', 'waiver_beneficiary_first_name')) {
                $table->string('waiver_beneficiary_first_name')->nullable()->after('waiver_review_notes');
            }
            if (!Schema::hasColumn('applications', 'waiver_beneficiary_last_name')) {
                $table->string('waiver_beneficiary_last_name')->nullable()->after('waiver_beneficiary_first_name');
            }
            if (!Schema::hasColumn('applications', 'waiver_offered_date')) {
                $table->date('waiver_offered_date')->nullable()->after('waiver_beneficiary_last_name');
            }
            if (!Schema::hasColumn('applications', 'waiver_offered_by_name')) {
                $table->string('waiver_offered_by_name')->nullable()->after('waiver_offered_date');
            }
            if (!Schema::hasColumn('applications', 'waiver_original_name')) {
                $table->string('waiver_original_name')->nullable()->after('waiver_offered_by_name');
            }
            if (!Schema::hasColumn('applications', 'waiver_mime')) {
                $table->string('waiver_mime', 100)->nullable()->after('waiver_original_name');
            }
            if (!Schema::hasColumn('applications', 'waiver_file_hash')) {
                $table->string('waiver_file_hash', 64)->nullable()->after('waiver_mime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $cols = [
                'proof_payer_first_name','proof_payer_last_name','proof_payment_date','proof_amount_paid','proof_bank_name',
                'proof_original_name','proof_mime','proof_file_hash',
                'waiver_beneficiary_first_name','waiver_beneficiary_last_name','waiver_offered_date','waiver_offered_by_name',
                'waiver_original_name','waiver_mime','waiver_file_hash',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('applications', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
