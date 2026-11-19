<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('event_category')->nullable()->after('title');
            $table->string('event_mode')->nullable()->after('event_category');
            $table->string('virtual_link')->nullable()->after('event_mode');
            $table->boolean('registration_required')->default(false)->after('virtual_link');
            $table->string('registration_url')->nullable()->after('registration_required');
            $table->date('registration_deadline')->nullable()->after('registration_url');
            $table->integer('capacity')->nullable()->after('registration_deadline');
            $table->string('organizer')->nullable()->after('capacity');
            $table->string('contact_name')->nullable()->after('organizer');
            $table->string('contact_phone')->nullable()->after('contact_name');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->text('agenda')->nullable()->after('contact_email');
            $table->text('speakers')->nullable()->after('agenda');
            $table->string('target_audience')->nullable()->after('speakers');
            $table->string('cost')->nullable()->after('target_audience');
            $table->text('payment_details')->nullable()->after('cost');
            $table->boolean('recurring')->default(false)->after('payment_details');
            $table->string('recurrence_pattern')->nullable()->after('recurring');
            $table->boolean('reminder_email_1day')->default(false)->after('recurrence_pattern');
            $table->boolean('reminder_sms_1hour')->default(false)->after('reminder_email_1day');
            $table->string('event_status')->default('Upcoming')->after('reminder_sms_1hour');
            $table->string('venue_directions')->nullable()->after('event_status');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'event_category',
                'event_mode',
                'virtual_link',
                'registration_required',
                'registration_url',
                'registration_deadline',
                'capacity',
                'organizer',
                'contact_name',
                'contact_phone',
                'contact_email',
                'agenda',
                'speakers',
                'target_audience',
                'cost',
                'payment_details',
                'recurring',
                'recurrence_pattern',
                'reminder_email_1day',
                'reminder_sms_1hour',
                'event_status',
                'venue_directions',
            ]);
        });
    }
};
