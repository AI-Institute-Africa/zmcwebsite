<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // If the column doesn't exist yet, don't do anything.
        // DB-agnostic column existence check (works on SQLite/MySQL/Postgres)
        if (!Schema::hasColumn('users', 'account_type')) {
            return;
        }

        // Any user who has at least one role is a STAFF user
        // SQLite does not support table aliases in UPDATE. Keep it portable.
        DB::statement("UPDATE users SET account_type = 'staff' WHERE EXISTS (SELECT 1 FROM model_has_roles m WHERE m.model_type = 'App\\\\Models\\\\User' AND m.model_id = users.id)");

        // Anything missing becomes PUBLIC
        DB::table('users')->whereNull('account_type')->update(['account_type' => 'public']);
    }

    public function down(): void
    {
        // no-op
    }
};
