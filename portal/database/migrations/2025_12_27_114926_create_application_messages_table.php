<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('application_messages', function (Blueprint $table) {
      $table->id();
      $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();

      $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();

      $table->enum('channel', ['internal', 'email'])->default('internal');
      $table->string('subject')->nullable();
      $table->longText('body');

      $table->timestamp('sent_at')->nullable();
      $table->timestamps();

      $table->index(['application_id','sent_at']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('application_messages');
  }
};
