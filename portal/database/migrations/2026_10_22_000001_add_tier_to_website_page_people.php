<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('website_page_people', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier')->default(3)->after('page_key');
            $table->index(['page_key', 'tier', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('website_page_people', function (Blueprint $table) {
            $table->dropIndex(['page_key', 'tier', 'sort_order']);
            $table->dropColumn('tier');
        });
    }
};
