<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (!Schema::hasColumn('notices', 'notice_category')) {
                $table->string('notice_category')->nullable()->after('title');
            }
            if (!Schema::hasColumn('notices', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('notice_category');
            }
            if (!Schema::hasColumn('notices', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('urgency_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn([
                'notice_category',
                'reference_number',
                'effective_date'
            ]);
        });
    }
};
