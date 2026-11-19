<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('login_activities')) {
            return;
        }

        if (Schema::hasColumn('login_activities', 'user_id')) {
            Schema::table('login_activities', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        if (Schema::hasColumn('login_activities', 'account_name')) {
            Schema::table('login_activities', function (Blueprint $table): void {
                $table->string('account_name')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Failed login rows may have no matching user or known account name,
        // so reverting these columns to NOT NULL would make existing data unsafe.
    }
};
