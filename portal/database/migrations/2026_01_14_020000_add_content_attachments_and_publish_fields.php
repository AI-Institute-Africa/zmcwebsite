<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->string('image_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime')->nullable();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable();
            $table->string('image_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['image_path','attachment_path','attachment_original_name','attachment_mime']);
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['published_at','image_path','attachment_path','attachment_original_name','attachment_mime']);
        });
    }
};
