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
        Schema::table('payments', function (Blueprint $table) {
            // Add PayNow-specific columns if they don't exist
            if (!Schema::hasColumn('payments', 'gateway')) {
                $table->string('gateway')->nullable()->default('paynow')->comment('Payment gateway used');
            }
            if (!Schema::hasColumn('payments', 'paynow_reference')) {
                $table->string('paynow_reference')->nullable()->comment('PayNow transaction reference');
            }
            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->enum('payment_method', ['paynow', 'ecocash', 'onemoney'])->nullable()->comment('Payment method used');
            }
            if (!Schema::hasColumn('payments', 'phone_number')) {
                $table->string('phone_number')->nullable()->comment('Phone number for mobile payments');
            }
            if (!Schema::hasColumn('payments', 'instructions')) {
                $table->text('instructions')->nullable()->comment('Payment instructions for mobile payments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumnIfExists('gateway');
            $table->dropColumnIfExists('paynow_reference');
            $table->dropColumnIfExists('payment_method');
            $table->dropColumnIfExists('phone_number');
            $table->dropColumnIfExists('instructions');
        });
    }
};
