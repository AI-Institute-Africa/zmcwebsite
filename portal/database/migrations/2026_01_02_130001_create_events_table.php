<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            // journalist | mediahouse | both
            $table->enum('target_portal', ['journalist','mediahouse','both'])->default('both');
            $table->string('location')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['target_portal','is_published','starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
