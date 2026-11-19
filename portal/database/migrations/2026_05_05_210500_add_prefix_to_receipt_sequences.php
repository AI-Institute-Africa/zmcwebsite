<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receipt_sequences', function (Blueprint $table) {
            if (!Schema::hasColumn('receipt_sequences', 'prefix')) {
                $table->string('prefix', 10)->default('RCT')->after('id');
            }
        });

        // Drop old unique index if it exists in a separate schema block so try/catch works at execution time
        try {
            Schema::table('receipt_sequences', function (Blueprint $table) {
                $table->dropUnique('receipt_sequences_year_unique');
            });
        } catch (\Exception $e) {
            // Ignore if index doesn't exist
        }

        try {
            Schema::table('receipt_sequences', function (Blueprint $table) {
                $table->unique(['prefix', 'year']);
            });
        } catch (\Exception $e) {
            // Ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_sequences', function (Blueprint $table) {
            $table->dropUnique(['prefix', 'year']);
            $table->unique(['year']);
            $table->dropColumn('prefix');
        });
    }
};
