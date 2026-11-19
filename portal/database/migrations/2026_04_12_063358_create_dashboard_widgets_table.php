<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dashboard_id');
            $table->string('widget_type', 30);
            $table->string('title', 200);
            $table->json('config')->nullable();
            $table->integer('position')->default(0);
            $table->string('size', 20)->default('col-md-6');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('dashboard_id')->references('id')->on('custom_dashboards')->onDelete('cascade');
            $table->index(['dashboard_id', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
