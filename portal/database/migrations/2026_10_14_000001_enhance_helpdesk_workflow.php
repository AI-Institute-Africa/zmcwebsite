<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_tickets', function (Blueprint $table) {
            $table->foreignId('incident_id')->nullable()->unique()->after('assigned_to')
                ->constrained('system_incidents')->nullOnDelete();
            $table->string('attachment_path')->nullable()->after('incident_id');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });

        Schema::create('helpdesk_ticket_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type')->default('comment');
            $table->text('body')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_updates');

        Schema::table('helpdesk_tickets', function (Blueprint $table) {
            $table->dropForeign(['incident_id']);
            $table->dropUnique(['incident_id']);
            $table->dropColumn(['incident_id', 'attachment_path', 'attachment_name']);
        });
    }
};
