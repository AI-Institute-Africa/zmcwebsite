<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('application_documents', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('application_documents', 'mime')) {
                $table->string('mime')->nullable()->after('original_name');
            }
            if (!Schema::hasColumn('application_documents', 'size')) {
                $table->unsignedBigInteger('size')->nullable()->after('mime');
            }
            if (!Schema::hasColumn('application_documents', 'sha256')) {
                $table->string('sha256', 64)->nullable()->after('size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropColumn(['owner_id', 'mime', 'size', 'sha256']);
        });
    }
};
