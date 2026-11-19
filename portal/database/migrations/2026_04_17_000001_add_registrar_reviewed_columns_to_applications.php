<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            if (!Schema::hasColumn('applications', 'registrar_reviewed_at')) {
                $t->timestamp('registrar_reviewed_at')->nullable();
            }
            if (!Schema::hasColumn('applications', 'registrar_reviewed_by')) {
                $t->unsignedBigInteger('registrar_reviewed_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            if (Schema::hasColumn('applications', 'registrar_reviewed_at')) {
                $t->dropColumn('registrar_reviewed_at');
            }
            if (Schema::hasColumn('applications', 'registrar_reviewed_by')) {
                $t->dropColumn('registrar_reviewed_by');
            }
        });
    }
};
