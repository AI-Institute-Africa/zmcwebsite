<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renewal_applications', function (Blueprint $table) {
            $table->string('original_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('renewal_applications', function (Blueprint $table) {
            $table->string('original_number')->nullable(false)->change();
        });
    }
};
