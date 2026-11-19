<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_house_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_house_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('journalist_user_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('active'); // active, pending, removed
            $table->string('role')->nullable(); // editor, reporter, etc.
            $table->timestamps();

            $table->unique(['media_house_user_id', 'journalist_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_house_staff');
    }
};
