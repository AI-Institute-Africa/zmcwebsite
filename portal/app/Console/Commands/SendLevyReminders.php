<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\LevyRecord;
use App\Models\RegistrationRecord;
use App\Mail\LevyPaymentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendLevyReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'levy:send-reminders 
                            {--year= : Financial year for levy reminders (defaults to current year)}
                            {--due-date= : Due date for levy payment (defaults to end of March)}
                            {--test : Send test emails only to first 5 users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send annual levy payment reminders to media houses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentYear = now()->year;
        $financialYear = $this->option('year') ?? (string) $currentYear;
        $dueDate = $this->option('due-date') 
            ? Carbon::parse($this->option('due-date'))
            : Carbon::create($currentYear, 3, 31)->endOfDay(); // Default: March 31st
        
        $isTest = $this->option('test');

        $this->info("Sending levy reminders for financial year: {$financialYear}");
        $this->info("Due date: {$dueDate->format('d F Y')}");
        
        if ($isTest) {
            $this->warn("TEST MODE: Only first 5 users will receive emails");
        }

        // Get all media houses (users with registered media houses)
        $mediaHouseUsers = User::where('account_type', 'public')
            ->whereHas('applications', function($q) {
                $q->where('application_type', 'registration')
                  ->whereIn('status', [
                      'issued',
                      'printed',
                      'produced_ready',
                      'card_generated',
                      'cert_generated'
                  ]);
            })
            ->get();

        if ($isTest) {
            $mediaHouseUsers = $mediaHouseUsers->take(5);
        }

        $this->info("Found {$mediaHouseUsers->count()} media house users");

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($mediaHouseUsers->count());
        $progressBar->start();

        foreach ($mediaHouseUsers as $user) {
            // Check if user has already paid levy for this financial year
            $hasPaid = LevyRecord::where('user_id', $user->id)
                ->where('financial_year', $financialYear)
                ->whereIn('payment_status', ['paid', 'verified', 'approved', 'completed'])
                ->exists();

            if ($hasPaid) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            // Check if there's a pending submission
            $hasPending = LevyRecord::where('user_id', $user->id)
                ->where('financial_year', $financialYear)
                ->where('payment_status', 'pending')
                ->exists();

            if ($hasPending) {
                // Don't send reminder if they have pending submission
                $skipped++;
                $progressBar->advance();
                continue;
            }

            // Send reminder email
            try {
                Mail::to($user->email)->send(new LevyPaymentReminder($user, $financialYear, $dueDate));
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("\nFailed to send email to {$user->email}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("==============================================");
        $this->info("Levy Reminder Summary");
        $this->info("==============================================");
        $this->info("Financial Year: {$financialYear}");
        $this->info("Due Date: {$dueDate->format('d F Y')}");
        $this->info("----------------------------------------------");
        $this->info("Total Media Houses: {$mediaHouseUsers->count()}");
        $this->info("Reminders Sent: {$sent}");
        $this->info("Skipped (Already Paid/Pending): {$skipped}");
        $this->info("Failed: {$failed}");
        $this->info("==============================================");

        return Command::SUCCESS;
    }
}
