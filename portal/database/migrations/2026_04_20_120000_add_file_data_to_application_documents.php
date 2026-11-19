<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('application_documents', 'file_data')) {
                $table->binary('file_data')->nullable()->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            if (Schema::hasColumn('application_documents', 'file_data')) {
                $table->dropColumn('file_data');
            }
        });
    }
};
