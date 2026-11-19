<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_records', function (Blueprint $table) {
            $table->string('organisation_name')->nullable()->after('entity_name');
            $table->text('directors')->nullable();
            $table->string('directors_gender')->nullable();
            $table->text('shareholding')->nullable();
            $table->text('physical_address')->nullable();
            $table->string('town')->nullable();
            $table->string('province')->nullable();
            $table->string('phone')->nullable();
            $table->string('cell')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('category')->nullable();
            $table->string('registration_status')->nullable();
            $table->date('registration_date')->nullable();
            $table->integer('registration_year')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_email')->nullable();
            $table->string('contact_person_phone')->nullable();

            $table->index('registration_year');
            $table->index('category');
            $table->index('province');
        });

        if (!Schema::hasTable('media_house_publications')) {
            Schema::create('media_house_publications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('registration_record_id');
                $table->string('publication_name')->nullable();
                $table->string('type')->nullable();
                $table->string('medium')->nullable();
                $table->string('frequency')->nullable();
                $table->string('language')->nullable();
                $table->string('focus')->nullable();
                $table->string('scope')->nullable();
                $table->text('reach_cities')->nullable();
                $table->text('reach_provinces')->nullable();
                $table->string('editor_manager')->nullable();
                $table->string('pub_website')->nullable();
                $table->string('pub_email')->nullable();
                $table->string('pub_contacts')->nullable();
                $table->string('operational_status')->nullable();
                $table->date('license_expiry')->nullable();
                $table->text('renewal_history')->nullable();
                $table->timestamps();

                $table->foreign('registration_record_id')
                    ->references('id')
                    ->on('registration_records')
                    ->onDelete('cascade');
                $table->index('registration_record_id');
                $table->index('operational_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_house_publications');

        Schema::table('registration_records', function (Blueprint $table) {
            $table->dropIndex(['registration_year']);
            $table->dropIndex(['category']);
            $table->dropIndex(['province']);
            $table->dropColumn([
                'organisation_name', 'directors', 'directors_gender', 'shareholding',
                'physical_address', 'town', 'province', 'phone', 'cell', 'email', 'website',
                'category', 'registration_status', 'registration_date', 'registration_year',
                'contact_person_name', 'contact_person_email', 'contact_person_phone',
            ]);
        });
    }
};
