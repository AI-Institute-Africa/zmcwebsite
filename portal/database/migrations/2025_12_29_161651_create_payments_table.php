<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('application_id');

            // paynow | proof | waiver
            $table->string('method', 50);

            // paynow reference or internal ref
            $table->string('reference')->nullable();

            // pending|paid|failed|approved|rejected
            $table->string('status')->default('pending');

            $table->string('poll_url')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 10)->nullable();

            // webhook payload snapshot / proof meta / waiver meta
            $table->json('raw')->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'method']);

            // Optional FK (enable if applications table exists and engine supports it)
            // $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
