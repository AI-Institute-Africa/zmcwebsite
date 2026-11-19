<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The applicant portal stores uploaded documents with status "draft" while an
 * application is still being saved as a draft. The original enum only allowed
 * ("pending","accepted","rejected"), which caused:
 *
 *   SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status'
 *
 * This migration widens the enum to include "draft".
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `application_documents` MODIFY COLUMN `status` ENUM('pending','accepted','rejected','draft') NOT NULL DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE application_documents DROP CONSTRAINT IF EXISTS application_documents_status_check');
            DB::statement("ALTER TABLE application_documents ADD CONSTRAINT application_documents_status_check CHECK (status::text = ANY(ARRAY['pending','accepted','rejected','draft']))");
        } else {
            Schema::table('application_documents', function ($table) {
                $table->string('status', 32)->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `application_documents` MODIFY COLUMN `status` ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
