<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'form_data')) {
                $table->json('form_data')->nullable()->after('collection_region');
            }
            if (!Schema::hasColumn('applications', 'is_draft')) {
                $table->boolean('is_draft')->default(false)->after('form_data');
            }
            if (!Schema::hasColumn('applications', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('is_draft');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['form_data', 'is_draft', 'submitted_at']);
        });
    }
};
