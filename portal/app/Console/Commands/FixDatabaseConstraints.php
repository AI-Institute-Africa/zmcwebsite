<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixDatabaseConstraints extends Command
{
    protected $signature = 'db:fix-constraints';

    protected $description = 'Fix database CHECK constraints to match application requirements';

    public function handle(): int
    {
        $this->info('Fixing database constraints...');

        $isSqlite = \DB::connection()->getDriverName() === 'sqlite';

        try {
            if (! $isSqlite) {
                DB::statement('ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check');
                DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status::text = ANY(ARRAY[
                    'draft', 'submitted', 'withdrawn', 'needs_correction',
                    'officer_review', 'officer_approved', 'officer_rejected',
                    'correction_requested', 'returned_to_applicant',
                    'approved_awaiting_payment', 'forwarded_to_registrar', 'registrar_fix_request',
                    'registrar_review', 'registrar_approved', 'registrar_rejected',
                    'returned_to_officer', 'pending_accounts_from_registrar', 'registrar_approved_pending_reg_fee',
                    'accounts_review', 'awaiting_accounts_verification',
                    'payment_verified', 'payment_rejected',
                    'paid_confirmed', 'returned_to_accounts',
                    'submitted_with_app_fee', 'verified_by_officer',
                    'approved_pending_payment', 'paid', 'returned_from_payments', 'returned_from_registrar', 'rejected',
                    'production_queue', 'produced_ready', 'card_generated', 'certificate_generated', 'printed', 'issued'
                ]::text[]))");
                $this->info('  - applications.status constraint updated');

                DB::statement('ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_collection_region_check');
                DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_collection_region_check CHECK (collection_region IS NULL OR collection_region::text = ANY(ARRAY[
                    'harare', 'bulawayo', 'mutare', 'masvingo', 'gweru', 'chinhoyi'
                ]::text[]))");
                $this->info('  - applications.collection_region constraint updated');

                DB::statement("ALTER TABLE applications ALTER COLUMN status SET DEFAULT 'draft'");
                DB::statement('ALTER TABLE applications ALTER COLUMN collection_region DROP NOT NULL');
            }
            $this->info('  - applications defaults updated');

            if (! $isSqlite) {
                DB::statement('ALTER TABLE application_documents DROP CONSTRAINT IF EXISTS application_documents_status_check');
                DB::statement("ALTER TABLE application_documents ADD CONSTRAINT application_documents_status_check CHECK (status::text = ANY(ARRAY[
                    'pending', 'accepted', 'rejected', 'draft', 'uploaded'
                ]::text[]))");
                $this->info('  - application_documents.status constraint updated');
            }

            foreach (['owner_id', 'mime', 'size', 'sha256', 'thumbnail_path'] as $col) {
                if (! Schema::hasColumn('application_documents', $col)) {
                    Schema::table('application_documents', function ($table) use ($col) {
                        $table->string($col)->nullable()->after('file_path');
                    });
                }
            }
            if (! Schema::hasColumn('application_documents', 'file_data')) {
                Schema::table('application_documents', function ($table) {
                    $table->binary('file_data')->nullable()->after('file_path');
                });
            }
            $this->info('  - application_documents missing columns checked');

            if (! $this->columnExists('files', 'id')) {
                if ($isSqlite) {
                    DB::statement('CREATE TABLE IF NOT EXISTS files (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        owner_id BIGINT,
                        application_id BIGINT,
                        path VARCHAR(255),
                        mime VARCHAR(255),
                        size BIGINT,
                        sha256 VARCHAR(64),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )');
                } else {
                    DB::statement('CREATE TABLE IF NOT EXISTS files (
                        id BIGSERIAL PRIMARY KEY,
                        owner_id BIGINT,
                        application_id BIGINT,
                        path VARCHAR(255),
                        mime VARCHAR(255),
                        size BIGINT,
                        sha256 VARCHAR(64),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )');
                }
                $this->info('  - files table created');
            }

            $this->info('All constraints fixed successfully!');

            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to fix constraints: '.$e->getMessage());

            return 1;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $cols = DB::select("PRAGMA table_info({$table})");
            foreach ($cols as $col) {
                if ($col->name === $column) {
                    return true;
                }
            }

            return false;
        }

        return DB::selectOne(
            'SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
            [$table, $column]
        ) !== null;
    }
}
