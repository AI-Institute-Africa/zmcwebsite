<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'internal_notes')) {
                $table->text('internal_notes')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'payment_amount')) {
                $table->decimal('payment_amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('complaints', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->unique();
            }
            if (Schema::hasColumn('complaints', 'payment_status')) {
                $table->string('payment_status')->nullable()->change();
            }
            if (Schema::hasColumn('complaints', 'status')) {
                $table->string('status')->default('open')->change();
            }
        });

        if (!Schema::hasTable('appeal_payments')) {
            Schema::create('appeal_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
                $table->string('reference')->unique();
                $table->string('provider_reference')->nullable()->index();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10);
                $table->string('status', 32)->default('initiating')->index();
                $table->text('poll_url')->nullable();
                $table->json('gateway_response')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
                $table->index(['complaint_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appeal_payments');

        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'idempotency_key')) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }
            foreach (['internal_notes', 'payment_amount'] as $column) {
                if (Schema::hasColumn('complaints', $column)) {
                    $table->dropColumn($column);
                }
            }
            // Keep status columns widened so workflow values already written
            // remain valid after rollback.
        });
    }
};
