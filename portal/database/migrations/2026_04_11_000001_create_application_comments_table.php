<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->string('user_role', 50);
            $table->string('visibility', 20)->default('internal');
            $table->string('comment_type', 30)->default('general');
            $table->text('body');
            $table->string('application_status_at_time', 60)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['application_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('comment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_comments');
    }
};
