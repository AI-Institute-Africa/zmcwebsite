<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('news') && !Schema::hasColumn('news', 'image_paths')) {
            Schema::table('news', function (Blueprint $table) {
                $table->json('image_paths')->nullable();
            });
        }

        if (Schema::hasTable('tenders') && !Schema::hasColumn('tenders', 'quantity')) {
            Schema::table('tenders', function (Blueprint $table) {
                $table->string('quantity', 120)->nullable();
            });
        }

        if (Schema::hasTable('news') && Schema::hasColumn('news', 'category')) {
            DB::table('news')->where('category', 'Statement')->update(['category' => 'Press Release']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news') && Schema::hasColumn('news', 'image_paths')) {
            Schema::table('news', fn (Blueprint $table) => $table->dropColumn('image_paths'));
        }
        if (Schema::hasTable('tenders') && Schema::hasColumn('tenders', 'quantity')) {
            Schema::table('tenders', fn (Blueprint $table) => $table->dropColumn('quantity'));
        }
    }
};
