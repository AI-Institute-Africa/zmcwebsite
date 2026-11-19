<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('officer_followups', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['expiry','renewal','verification','compliance'])->default('expiry')->index();
            $table->enum('status', ['open','in_progress','done'])->default('open')->index();

            $table->string('title')->nullable();
            $table->text('notes')->nullable();

            // Optional linkage
            $table->string('entity_type')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->index();

            $table->timestamps();

            $table->unique(['type','entity_type','entity_id'], 'followups_unique_entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officer_followups');
    }
};
