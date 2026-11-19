<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'status')) {
                $table->string('status')->default('submitted')->index();
            }

            if (!Schema::hasColumn('applications', 'current_stage')) {
                $table->string('current_stage')->nullable()->index();
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
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'last_action_by')) {
                $table->dropConstrainedForeignId('last_action_by');
            }

            // Optional: only drop if you really want rollback to remove them
            // if (Schema::hasColumn('applications', 'status')) $table->dropColumn('status');
            // if (Schema::hasColumn('applications', 'current_stage')) $table->dropColumn('current_stage');
            // if (Schema::hasColumn('applications', 'last_action_at')) $table->dropColumn('last_action_at');
            // if (Schema::hasColumn('applications', 'correction_notes')) $table->dropColumn('correction_notes');
            // if (Schema::hasColumn('applications', 'rejection_reason')) $table->dropColumn('rejection_reason');
        });
    }
};
