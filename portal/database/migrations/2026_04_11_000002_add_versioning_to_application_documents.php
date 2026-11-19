<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('replaced_by_id')->nullable()->after('id');
            $table->unsignedBigInteger('replaces_id')->nullable()->after('replaced_by_id');
            $table->unsignedInteger('version')->default(1)->after('replaces_id');
            $table->boolean('is_current')->default(true)->after('version');
            $table->string('superseded_reason', 100)->nullable()->after('is_current');
            $table->timestamp('superseded_at')->nullable()->after('superseded_reason');

            $table->index(['application_id', 'doc_type', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex(['application_id', 'doc_type', 'is_current']);
            $table->dropColumn(['replaced_by_id', 'replaces_id', 'version', 'is_current', 'superseded_reason', 'superseded_at']);
        });
    }
};
