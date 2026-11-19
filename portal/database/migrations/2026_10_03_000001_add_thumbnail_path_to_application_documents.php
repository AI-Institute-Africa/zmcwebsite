<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('application_documents') && !Schema::hasColumn('application_documents', 'thumbnail_path')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->string('thumbnail_path', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('application_documents') && Schema::hasColumn('application_documents', 'thumbnail_path')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->dropColumn('thumbnail_path');
            });
        }
    }
};
