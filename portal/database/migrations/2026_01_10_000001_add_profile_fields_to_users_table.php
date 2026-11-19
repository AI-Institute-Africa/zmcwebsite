<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type', 20)->default('public')->after('role')->index(); // public | staff
            }
            if (!Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 5)->nullable()->after('account_type');
            }
            if (!Schema::hasColumn('users', 'phone_country_code')) {
                $table->string('phone_country_code', 10)->nullable()->after('locale');
            }
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number', 30)->nullable()->after('phone_country_code');
            }
            if (!Schema::hasColumn('users', 'profile_data')) {
                $table->json('profile_data')->nullable()->after('phone_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_data')) $table->dropColumn('profile_data');
            if (Schema::hasColumn('users', 'phone_number')) $table->dropColumn('phone_number');
            if (Schema::hasColumn('users', 'phone_country_code')) $table->dropColumn('phone_country_code');
            if (Schema::hasColumn('users', 'locale')) $table->dropColumn('locale');
            if (Schema::hasColumn('users', 'account_type')) $table->dropColumn('account_type');
        });
    }
};
