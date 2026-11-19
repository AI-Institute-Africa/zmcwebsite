<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('document_versions', 'field_name')) {
                $table->string('field_name')->nullable();
            }
            if (!Schema::hasColumn('document_versions', 'edited_at')) {
                $table->timestamp('edited_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            if (Schema::hasColumn('document_versions', 'field_name')) {
                $table->dropColumn('field_name');
            }
            if (Schema::hasColumn('document_versions', 'edited_at')) {
                $table->dropColumn('edited_at');
            }
        });
    }
};
