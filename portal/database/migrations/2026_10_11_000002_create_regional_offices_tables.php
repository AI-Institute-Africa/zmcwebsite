<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_offices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 160);
            $table->string('region', 100)->nullable()->index();
            $table->string('address', 500)->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('schedule', 200)->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('regional_office_staff', function (Blueprint $table) {
            $table->foreignId('regional_office_id')->constrained('regional_offices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['regional_office_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_office_staff');
        Schema::dropIfExists('regional_offices');
    }
};
