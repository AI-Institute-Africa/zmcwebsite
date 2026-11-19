<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_builder_states', function (Blueprint $table) {
            $table->id();
            $table->string('form_type', 30)->unique();
            $table->json('draft_schema')->nullable();
            $table->json('published_schema')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->unsignedBigInteger('draft_updated_by')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('form_builder_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('form_type', 30);
            $table->unsignedInteger('version');
            $table->json('schema');
            $table->string('change_summary', 500)->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();
            $table->unique(['form_type', 'version']);
        });

        // Seed version state from the live custom fields already in use.
        foreach (['accreditation', 'ap1', 'ap2', 'ap4'] as $formType) {
            $schema = DB::table('custom_form_fields')
                ->where('form_type', $formType)
                ->orderBy('step_number')
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($field) => [
                    'field_key' => (string) $field->id,
                    'field_name' => $field->field_name,
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                    'options' => $field->options ? (json_decode($field->options, true) ?: []) : [],
                    'step_number' => (int) $field->step_number,
                    'sort_order' => (int) $field->sort_order,
                    'is_required' => (bool) $field->is_required,
                    'placeholder' => $field->placeholder,
                    'help_text' => $field->help_text,
                    'is_active' => (bool) $field->is_active,
                    'validation_rules' => $field->validation_rules,
                ])->values()->all();

            DB::table('form_builder_states')->insert([
                'form_type' => $formType,
                'draft_schema' => json_encode($schema),
                'published_schema' => json_encode($schema),
                'version' => 1,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('form_builder_revisions')->insert([
                'form_type' => $formType,
                'version' => 1,
                'schema' => json_encode($schema),
                'change_summary' => 'Initial published form configuration',
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form_builder_revisions');
        Schema::dropIfExists('form_builder_states');
    }
};
