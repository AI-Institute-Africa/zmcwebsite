<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('officer_regions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $table->enum('region', ['harare', 'bulawayo', 'mutare', 'masvingo']);
      $table->timestamps();

      $table->unique(['user_id','region']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('officer_regions');
  }
};
