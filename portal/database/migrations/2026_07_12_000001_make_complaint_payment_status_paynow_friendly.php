<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('complaints', 'payment_status')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE complaints MODIFY payment_status VARCHAR(50) NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE complaints ALTER COLUMN payment_status TYPE VARCHAR(50)');
        }
    }

    public function down(): void
    {
        // Keep the wider status column; the application uses paid/pending/failed states.
    }
};
