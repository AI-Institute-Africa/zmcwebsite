<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('application_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('application_messages', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('sent_at');
                $table->index(['to_user_id','read_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_messages', function (Blueprint $table) {
            if (Schema::hasColumn('application_messages', 'read_at')) {
                $table->dropIndex(['to_user_id','read_at']);
                $table->dropColumn('read_at');
            }
        });
    }
};
