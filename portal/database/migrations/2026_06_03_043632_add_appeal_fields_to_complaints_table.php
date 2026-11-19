<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Payment reference from PayNow (appeals require payment)
            $table->string('payment_ref')->nullable()->after('attachment_mime');
            // Proof of payment file upload (to be reviewed by Accounts Officer)
            $table->string('payment_proof_path')->nullable()->after('payment_ref');
            $table->string('payment_proof_original_name')->nullable()->after('payment_proof_path');
            $table->string('payment_proof_mime')->nullable()->after('payment_proof_original_name');
            // Track accounts officer approval
            $table->enum('payment_status', ['pending','approved','rejected'])->nullable()->after('payment_proof_mime');
            $table->unsignedBigInteger('payment_approved_by')->nullable()->after('payment_status');
            $table->timestamp('payment_approved_at')->nullable()->after('payment_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn([
                'payment_ref',
                'payment_proof_path',
                'payment_proof_original_name',
                'payment_proof_mime',
                'payment_status',
                'payment_approved_by',
                'payment_approved_at',
            ]);
        });
    }
};

