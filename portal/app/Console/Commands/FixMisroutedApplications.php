<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMisroutedApplications extends Command
{
    protected $signature = 'app:fix-misrouted-applications';
    protected $description = 'Reset applications that bypassed officer review back to submitted';

    public function handle()
    {
        $count = DB::table('applications')
            ->where('status', 'awaiting_accounts_verification')
            ->whereNull('approved_at')
            ->whereNull('registrar_approved_at')
            ->update([
                'status' => 'submitted',
                'current_stage' => 'officer_review',
            ]);

        $this->info("Fixed {$count} misrouted application(s).");

        return 0;
    }
}
