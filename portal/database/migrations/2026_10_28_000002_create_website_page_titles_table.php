<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('website_page_titles')) {
            return;
        }

        Schema::create('website_page_titles', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 32)->nullable()->index();
            $table->string('name', 160);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_page_titles');
    }
};
