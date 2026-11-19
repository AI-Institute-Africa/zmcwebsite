<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_media_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16)->index(); // image or video
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable()->index();
            $table->string('file_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_media_items');
    }
};
