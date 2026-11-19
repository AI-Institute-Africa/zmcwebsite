<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || !Schema::hasTable('applications')) {
            return;
        }

        if (Schema::hasColumn('applications', 'status')) {
            DB::statement("ALTER TABLE applications MODIFY status VARCHAR(80) NOT NULL DEFAULT 'submitted'");
        }

        if (Schema::hasColumn('applications', 'payment_status')) {
            DB::statement("ALTER TABLE applications MODIFY payment_status VARCHAR(100) NOT NULL DEFAULT 'none'");
        }

        if (Schema::hasColumn('applications', 'payment_submission_method')) {
            DB::statement('ALTER TABLE applications MODIFY payment_submission_method VARCHAR(80) NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        // Intentionally left irreversible: reverting to enums can truncate
        // production workflow statuses already written by the application.
    }
};
