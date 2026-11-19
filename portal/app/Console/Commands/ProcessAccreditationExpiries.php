<?php

namespace App\Console\Commands;

use App\Models\AccreditationRecord;
use App\Models\OfficerFollowUp;
use App\Models\RegistrationRecord;
use App\Notifications\ExpiryReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class ProcessAccreditationExpiries extends Command
{
    protected $signature = 'accreditation:process-expiries {--days=60 : Look ahead window in days}';

    protected $description = 'Create follow-ups and notify applicants whose accreditation/registration is expiring.';

    public function handle(): int
    {
        $lookAheadDays = (int) $this->option('days');
        $windowEnd = now()->addDays($lookAheadDays)->startOfDay();

        $this->info('Processing expiries up to '.$windowEnd->toDateString());

        $notified = 0;
        $followups = 0;

        // Only run if the necessary tables exist.
        $hasAcc = Schema::hasTable('accreditation_records');
        $hasReg = Schema::hasTable('registration_records');
        $hasFollowups = Schema::hasTable('officer_followups');

        if (!$hasAcc && !$hasReg) {
            $this->warn('No records tables found. Run migrations first.');
            return self::SUCCESS;
        }

        // Common reminder thresholds (days remaining)
        $milestones = [60, 30, 14, 7, 0];

        if ($hasAcc) {
            $notified += $this->processAccreditations($windowEnd, $milestones, $hasFollowups, $followups);
        }

        if ($hasReg) {
            $notified += $this->processRegistrations($windowEnd, $milestones, $hasFollowups, $followups);
        }

        $this->info("Notifications queued/sent: {$notified}");
        if ($hasFollowups) {
            $this->info("Follow-up items created/ensured: {$followups}");
        }

        return self::SUCCESS;
    }

    private function processAccreditations($windowEnd, array $milestones, bool $hasFollowups, int &$followups): int
    {
        $count = 0;
        $rows = AccreditationRecord::query()
            ->with('holder')
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $windowEnd)
            ->orderBy('expires_at')
            ->get();

        foreach ($rows as $r) {
            if (!$r->holder) {
                continue;
            }

            $days = (int) now()->startOfDay()->diffInDays($r->expires_at->startOfDay(), false);
            if (!in_array($days, $milestones, true) && $days > 0) {
                // Only notify on milestones to prevent spamming.
                continue;
            }

            Notification::send($r->holder, new ExpiryReminderNotification(
                recordType: 'Accreditation',
                recordNumber: $r->record_number ?? $r->certificate_no ?? ('#'.$r->id),
                expiresOn: optional($r->expires_at)->toDateString(),
                daysRemaining: max($days, 0),
            ));
            $count++;

            if ($hasFollowups) {
                $followups += $this->ensureFollowUp(
                    entityType: AccreditationRecord::class,
                    entityId: $r->id,
                    title: 'Accreditation expiring: '.($r->record_number ?? $r->certificate_no ?? ('#'.$r->id)),
                    dueDate: optional($r->expires_at)->toDateString(),
                );
            }
        }

        return $count;
    }

    private function processRegistrations($windowEnd, array $milestones, bool $hasFollowups, int &$followups): int
    {
        $count = 0;
        $rows = RegistrationRecord::query()
            ->with('contact')
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $windowEnd)
            ->orderBy('expires_at')
            ->get();

        foreach ($rows as $r) {
            if (!$r->contact) {
                continue;
            }

            $days = (int) now()->startOfDay()->diffInDays($r->expires_at->startOfDay(), false);
            if (!in_array($days, $milestones, true) && $days > 0) {
                continue;
            }

            Notification::send($r->contact, new ExpiryReminderNotification(
                recordType: 'Registration',
                recordNumber: $r->record_number ?? $r->registration_no ?? ('#'.$r->id),
                expiresOn: optional($r->expires_at)->toDateString(),
                daysRemaining: max($days, 0),
            ));
            $count++;

            if ($hasFollowups) {
                $followups += $this->ensureFollowUp(
                    entityType: RegistrationRecord::class,
                    entityId: $r->id,
                    title: 'Registration expiring: '.($r->record_number ?? $r->registration_no ?? ('#'.$r->id)),
                    dueDate: optional($r->expires_at)->toDateString(),
                );
            }
        }

        return $count;
    }

    private function ensureFollowUp(string $entityType, int $entityId, string $title, ?string $dueDate): int
    {
        $existing = OfficerFollowUp::query()
            ->where('type', 'expiry')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        if ($existing) {
            return 0;
        }

        OfficerFollowUp::create([
            'type' => 'expiry',
            'status' => 'open',
            'title' => $title,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'due_date' => $dueDate,
        ]);

        return 1;
    }
}
