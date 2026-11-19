<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')
            ->where('status', 'awaiting_accounts_verification')
            ->whereNull('approved_at')
            ->whereNull('registrar_approved_at')
            ->update([
                'status' => 'submitted',
                'current_stage' => 'officer_review',
            ]);
    }

    public function down(): void
    {
    }
};
