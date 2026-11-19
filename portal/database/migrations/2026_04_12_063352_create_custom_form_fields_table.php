<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('form_type', 30);
            $table->string('field_name', 100);
            $table->string('field_label', 200);
            $table->string('field_type', 30)->default('text');
            $table->json('options')->nullable();
            $table->integer('step_number')->default(1);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->string('placeholder', 255)->nullable();
            $table->string('help_text', 500)->nullable();
            $table->string('validation_rules', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['form_type', 'is_active', 'step_number', 'sort_order'], 'cff_form_active_step_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_form_fields');
    }
};
