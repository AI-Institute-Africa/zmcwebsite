<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveAuditLogs extends Command
{
    protected $signature = 'audit:archive-old-logs {--year=} {--chunk=1000}';
    protected $description = 'Move non-current-year audit logs to archive table';

    public function handle(): int
    {
        $currentYear = now()->year;
        $year = (int) ($this->option('year') ?: ($currentYear - 1));
        $chunk = max(100, (int) $this->option('chunk'));

        $this->info("Archiving audit logs for year {$year} and older...");

        $moved = 0;
        DB::table('audit_logs')
            ->whereYear('created_at', '<=', $year)
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$moved, $year) {
                $payload = [];
                $ids = [];
                foreach ($rows as $row) {
                    $ids[] = $row->id;
                    $payload[] = [
                        'id' => $row->id,
                        'actor_user_id' => $row->actor_user_id ?? null,
                        'user_id' => $row->user_id ?? null,
                        'user_role' => $row->user_role ?? null,
                        'action' => $row->action,
                        'action_type' => $row->action_type ?? null,
                        'module' => $row->module ?? null,
                        'model_type' => $row->model_type ?? null,
                        'model_id' => $row->model_id ?? null,
                        'record_number' => $row->record_number ?? null,
                        'office_branch' => $row->office_branch ?? null,
                        'old_value' => $row->old_value ?? null,
                        'new_value' => $row->new_value ?? null,
                        'rejection_reason' => $row->rejection_reason ?? null,
                        'is_system_generated' => (bool) ($row->is_system_generated ?? false),
                        'application_id' => $row->application_id ?? null,
                        'meta' => $row->meta ?? null,
                        'ip' => $row->ip ?? null,
                        'user_agent' => $row->user_agent ?? null,
                        'created_at' => $row->created_at,
                        'archived_at' => now(),
                        'archive_year' => $year,
                    ];
                }

                DB::table('audit_log_archives')->upsert($payload, ['id']);
                DB::table('audit_logs')->whereIn('id', $ids)->delete();
                $moved += count($ids);
                $this->line('Moved batch: ' . count($ids));
            });

        $this->info("Completed. Total moved: {$moved}");
        return self::SUCCESS;
    }
}
