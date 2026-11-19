<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('applications', 'form_type')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->string('form_type', 10)->nullable()->after('request_type');
            });
        }

        if (!Schema::hasColumn('applications', 'registration_type')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->string('registration_type', 50)->nullable()->after('form_type');
            });
        }

        Schema::create('levy_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->string('reference', 30)->unique();
            $table->string('entity_name');
            $table->string('postal_address')->nullable();
            $table->string('business_address')->nullable();
            $table->string('telephone')->nullable();
            $table->string('financial_year', 20);
            $table->string('media_category')->nullable();
            $table->string('subsidiary_name')->nullable();
            $table->string('subsidiary_address')->nullable();
            $table->decimal('gross_annual_turnover', 18, 2);
            $table->decimal('levy_rate', 5, 4)->default(0.005);
            $table->decimal('levy_amount', 18, 2);
            $table->string('currency', 5)->default('USD');
            $table->string('payment_status', 30)->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('receipt_number')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->json('form_data')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['user_id', 'financial_year']);
        });

        if (!Schema::hasTable('levy_documents')) {
            Schema::create('levy_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('levy_record_id');
                $table->string('document_type');
                $table->string('file_path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->timestamps();

                $table->foreign('levy_record_id')->references('id')->on('levy_records')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('levy_documents');
        Schema::dropIfExists('levy_records');

        if (Schema::hasColumn('applications', 'form_type')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('form_type');
            });
        }
        if (Schema::hasColumn('applications', 'registration_type')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('registration_type');
            });
        }
    }
};
