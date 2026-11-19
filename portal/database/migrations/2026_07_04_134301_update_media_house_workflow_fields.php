<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('app_fee_payment_method')->nullable();
            $table->string('app_fee_paynow_reference')->nullable();
            $table->string('app_fee_proof_path')->nullable();
            $table->timestamp('app_fee_verified_at')->nullable();
            $table->string('reg_fee_payment_method')->nullable();
            $table->string('reg_fee_paynow_reference')->nullable();
            $table->string('reg_fee_proof_path')->nullable();
            $table->timestamp('reg_fee_verified_at')->nullable();
            $table->timestamp('reg_fee_modal_triggered_at')->nullable();
            $table->timestamp('collection_email_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'app_fee_payment_method',
                'app_fee_paynow_reference',
                'app_fee_proof_path',
                'app_fee_verified_at',
                'reg_fee_payment_method',
                'reg_fee_paynow_reference',
                'reg_fee_proof_path',
                'reg_fee_verified_at',
                'reg_fee_modal_triggered_at',
                'collection_email_sent_at'
            ]);
        });
    }
};
