<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\User;
use Illuminate\Console\Command;

class ClearPublicAccounts extends Command
{
    protected $signature = 'zmc:clear-public {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Delete all non-staff (public) user accounts and their applications';

    public function handle()
    {
        $staffRoles = [
            'super_admin',
            'accreditation_officer', 'registrar', 'accounts_payments',
            'production', 'it_admin', 'auditor', 'director',
            'complaints_officer', 'pr_officer', 'public_info_compliance',
            'research_training',
        ];

        $publicUsers = User::whereDoesntHave('roles', fn($q) => $q->whereIn('name', $staffRoles))->get();

        if ($publicUsers->isEmpty()) {
            $this->info('No public accounts found.');
            return 0;
        }

        $this->warn("Found {$publicUsers->count()} public user(s)");
        $appCount = Application::whereIn('applicant_user_id', $publicUsers->pluck('id'))->count();
        $this->warn("  with {$appCount} application(s)");

        foreach ($publicUsers as $u) {
            $apps = Application::where('applicant_user_id', $u->id)->count();
            $this->line("  [{$u->id}] {$u->email} — {$apps} application(s)");
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing deleted.');
            return 0;
        }

        if (!$this->confirm('Delete these users and their applications?')) {
            $this->info('Cancelled.');
            return 1;
        }

        $bar = $this->output->createProgressBar($publicUsers->count());
        foreach ($publicUsers as $user) {
            Application::where('applicant_user_id', $user->id)->delete();
            $user->delete();
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Done.');
        return 0;
    }
}
