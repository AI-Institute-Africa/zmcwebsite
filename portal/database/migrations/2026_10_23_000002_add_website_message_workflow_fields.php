<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'priority')) {
                $table->string('priority', 20)->default('normal')->index();
            }
            if (!Schema::hasColumn('complaints', 'response_due_at')) {
                $table->timestamp('response_due_at')->nullable()->index();
            }
            if (!Schema::hasColumn('complaints', 'first_responded_at')) {
                $table->timestamp('first_responded_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            foreach (['priority', 'response_due_at', 'first_responded_at'] as $column) {
                if (Schema::hasColumn('complaints', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
