<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accreditation_records', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('expires_at');
            $table->timestamp('card_issued_at')->nullable()->after('collected_at');
        });

        Schema::table('registration_records', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accreditation_records', function (Blueprint $table) {
            $table->dropColumn(['collected_at', 'card_issued_at']);
        });

        Schema::table('registration_records', function (Blueprint $table) {
            $table->dropColumn('collected_at');
        });
    }
};
