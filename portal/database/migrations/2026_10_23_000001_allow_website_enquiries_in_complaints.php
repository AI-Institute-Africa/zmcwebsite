<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // The original column was an ENUM limited to complaint/appeal,
            // but the public contact endpoint stores website messages as enquiry.
            DB::statement("ALTER TABLE complaints MODIFY COLUMN type VARCHAR(32) NOT NULL DEFAULT 'complaint'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE complaints ALTER COLUMN type TYPE VARCHAR(32) USING type::text');
            DB::statement("ALTER TABLE complaints ALTER COLUMN type SET DEFAULT 'complaint'");
        }
    }

    public function down(): void
    {
        // Keep the widened column so existing enquiry records remain valid.
    }
};
