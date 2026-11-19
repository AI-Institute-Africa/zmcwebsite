<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_page_people', function (Blueprint $table) {
            if (!Schema::hasColumn('website_page_people', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('page_key')
                    ->constrained('website_page_sections')->nullOnDelete();
            }
            if (!Schema::hasColumn('website_page_people', 'title_id')) {
                $table->foreignId('title_id')->nullable()->after('section_id')
                    ->constrained('website_page_titles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('website_page_people', function (Blueprint $table) {
            if (Schema::hasColumn('website_page_people', 'section_id')) {
                $table->dropConstrainedForeignId('section_id');
            }
            if (Schema::hasColumn('website_page_people', 'title_id')) {
                $table->dropConstrainedForeignId('title_id');
            }
        });
    }
};
