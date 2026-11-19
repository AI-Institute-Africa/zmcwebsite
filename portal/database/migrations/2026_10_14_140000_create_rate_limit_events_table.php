<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rate_limit_events')) {
            return;
        }

        Schema::create('rate_limit_events', function (Blueprint $table): void {
            $table->id();
            $table->char('event_key', 64)->unique();
            $table->string('ip_address', 45)->index();
            $table->string('route_name')->nullable();
            $table->string('path')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('throttled_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limit_events');
    }
};
