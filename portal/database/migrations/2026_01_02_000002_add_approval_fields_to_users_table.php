<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('remember_token')->index();
            }
            if (!Schema::hasColumn('users', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at')->index();
            }
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status', 30)->default('active')->after('approved_by')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'approved_at')) $table->dropColumn('approved_at');
            if (Schema::hasColumn('users', 'approved_by')) $table->dropColumn('approved_by');
            if (Schema::hasColumn('users', 'account_status')) $table->dropColumn('account_status');
        });
    }
};
