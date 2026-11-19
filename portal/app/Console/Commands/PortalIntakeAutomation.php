<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Services\PortalIntakeAutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class PortalIntakeAutomation extends Command
{
    protected $signature = 'portal:intake-automation
                            {--limit=10 : Number of applications to process}
                            {--application-id= : Process a specific application ID}
                            {--commit : Actually save changes to database}
                            {--auto-approve : Include auto-approval decisions}
                            {--system-user-id= : System user ID for audit logging}';

    protected $description = 'Run automated intake approval checks on submitted applications';

    public function handle(PortalIntakeAutomationService $automationService): int
    {
        $limit = (int) $this->option('limit') ?? 10;
        $applicationId = $this->option('application-id');
        $commit = (bool) $this->option('commit');
        $autoApprove = (bool) $this->option('auto-approve');
        $systemUserId = $this->option('system-user-id');

        // Determine mode
        $mode = $commit ? 'COMMIT' : 'DRY-RUN';
        $this->line("<info>Mode: {$mode}</info>");

        if (!$commit) {
            $this->warn('Running in DRY-RUN mode. No database changes will be made. Use --commit to save changes.');
        }

        // Get applications to process
        if ($applicationId) {
            $applications = Application::where('id', $applicationId)->get();
            if ($applications->isEmpty()) {
                $this->error("Application {$applicationId} not found");
                return 1;
            }
        } else {
            $applications = Application::where('status', 'submitted')
                ->limit($limit)
                ->get();
        }

        if ($applications->isEmpty()) {
            $this->info('No applications to process');
            return 0;
        }

        $this->info("Processing " . $applications->count() . " application(s)...");

        $results = [
            'processed' => 0,
            'auto_passed' => 0,
            'flagged_for_review' => 0,
            'correction_needed' => 0,
            'errors' => 0,
        ];

        $progressBar = $this->output->createProgressBar($applications->count());
        $progressBar->start();

        foreach ($applications as $application) {
            try {
                $result = $automationService->processApplication(
                    $application,
                    [
                        'commit' => $commit,
                        'auto_approve' => $autoApprove,
                        'system_user_id' => $systemUserId,
                    ]
                );

                $decision = $result['decision']['decision'] ?? 'unknown';
                $score = $result['scores']['overall_score'] ?? 0;

                // Update results counter
                match ($decision) {
                    'auto_pass' => $results['auto_passed']++,
                    'flagged_for_review' => $results['flagged_for_review']++,
                    'correction_needed' => $results['correction_needed']++,
                    default => null,
                };

                $results['processed']++;

                if (isset($result['error']) && $result['error']) {
                    $results['errors']++;
                    $this->line("  <fg=red>✗</> Application #{$application->id}: Error - " . ($result['decision']['error'] ?? 'Unknown'));
                } else {
                    $status = match ($decision) {
                        'auto_pass' => '<fg=green>✓</> PASS',
                        'flagged_for_review' => '<fg=yellow>!</> REVIEW',
                        'correction_needed' => '<fg=red>!</> CORRECTION',
                        default => '<fg=gray>?</> UNKNOWN',
                    };
                    $this->line("  {$status} Application #{$application->id} ({$score}% confidence)");
                }
            } catch (\Exception $e) {
                $results['errors']++;
                $this->line("  <fg=red>✗</> Application #{$application->id}: Exception - " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Summary
        $this->line('');
        $this->line('<info>Summary:</info>');
        $this->line("  Processed: {$results['processed']}");
        $this->line("  Auto-passed: <fg=green>{$results['auto_passed']}</>");
        $this->line("  Flagged for review: <fg=yellow>{$results['flagged_for_review']}</>");
        $this->line("  Correction needed: <fg=red>{$results['correction_needed']}</>");
        if ($results['errors'] > 0) {
            $this->line("  Errors: <fg=red>{$results['errors']}</>");
        }

        if (!$commit) {
            $this->warn('DRY-RUN mode: No changes were saved to the database.');
            $this->line('Run with <fg=cyan>--commit</> flag to save changes.');
        } else {
            $this->info('Changes saved to database.');
        }

        return $results['errors'] > 0 ? 1 : 0;
    }
}
