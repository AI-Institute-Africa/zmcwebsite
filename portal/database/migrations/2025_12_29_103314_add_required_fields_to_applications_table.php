<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {

            if (!Schema::hasColumn('applications', 'reference')) {
                $table->string('reference', 50)->nullable()->index();
            }

            if (!Schema::hasColumn('applications', 'applicant_user_id')) {
                $table->foreignId('applicant_user_id')->nullable()->constrained('users')->nullOnDelete()->index();
            }

            if (!Schema::hasColumn('applications', 'application_type')) {
                $table->string('application_type', 50)->nullable()->index();
            }

            if (!Schema::hasColumn('applications', 'request_type')) {
                $table->string('request_type', 50)->nullable()->index();
            }

            if (!Schema::hasColumn('applications', 'journalist_scope')) {
                $table->string('journalist_scope', 50)->nullable()->index();
            }

            if (!Schema::hasColumn('applications', 'collection_region')) {
                $table->string('collection_region', 100)->nullable()->index();
            }

            if (!Schema::hasColumn('applications', 'status')) {
                $table->string('status', 50)->default('submitted')->index();
            }

            if (!Schema::hasColumn('applications', 'current_stage')) {
                $table->string('current_stage', 50)->nullable()->index();
            }

            if (!Schema::hasColumn('applications', 'last_action_at')) {
                $table->timestamp('last_action_at')->nullable();
            }

            if (!Schema::hasColumn('applications', 'last_action_by')) {
                $table->foreignId('last_action_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('applications', 'correction_notes')) {
                $table->text('correction_notes')->nullable();
            }

            if (!Schema::hasColumn('applications', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }

            // Optional stub (for Officer ID verification interface)
            if (!Schema::hasColumn('applications', 'id_verification_status')) {
                $table->string('id_verification_status', 20)->nullable()->index();
            }

            // Payments fields (some already exist in your model)
            if (!Schema::hasColumn('applications', 'paynow_reference')) {
                $table->string('paynow_reference', 200)->nullable()->index();
            }

            if (!Schema::hasColumn('applications', 'payment_status')) {
                $table->string('payment_status', 100)->nullable()->index();
            }

            if (!Schema::hasColumn('applications', 'waiver_path')) {
                $table->string('waiver_path', 255)->nullable();
            }

            if (!Schema::hasColumn('applications', 'decision_notes')) {
                $table->text('decision_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        // keep safe - optional rollback later
    }
};
