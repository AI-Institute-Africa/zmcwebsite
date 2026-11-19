<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (!Schema::hasColumn('tenders', 'issuing_organisation')) {
                $table->string('issuing_organisation', 190)->nullable();
            }
            if (!Schema::hasColumn('tenders', 'summary')) {
                $table->text('summary')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['issuing_organisation', 'summary'],
                fn (string $column) => Schema::hasColumn('tenders', $column)
            ));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
