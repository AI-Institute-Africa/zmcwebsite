<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('confirmed_at');
            }
            if (!Schema::hasColumn('payments', 'voided_at')) {
                $table->timestamp('voided_at')->nullable();
            }
            if (!Schema::hasColumn('payments', 'voided_by')) {
                $table->unsignedBigInteger('voided_by')->nullable();
            }
            if (!Schema::hasColumn('payments', 'void_reason')) {
                $table->text('void_reason')->nullable();
            }
            if (!Schema::hasColumn('payments', 'recorded_by')) {
                $table->unsignedBigInteger('recorded_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['receipt_number', 'payment_date', 'voided_at', 'voided_by', 'void_reason', 'recorded_by']);
        });
    }
};
