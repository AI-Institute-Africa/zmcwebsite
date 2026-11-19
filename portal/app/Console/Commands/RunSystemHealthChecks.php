<?php

namespace App\Console\Commands;

use App\Models\ModuleHealthStatus;
use App\Models\SystemIncident;
use App\Models\ServiceHeartbeat;
use App\Services\SystemAlertNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RunSystemHealthChecks extends Command
{
    protected $signature = 'system:monitor';

    protected $description = 'Probe essential portal services and refresh the IT monitoring dashboard.';

    private const MODULES = [
        ['applicant_portal', 'Applicant Portal', 'portal'],
        ['staff_dashboard', 'Staff Dashboard', 'portal'],
        ['authentication', 'Authentication Service', 'core'],
        ['file_upload', 'File Upload Service', 'core'],
        ['reporting', 'Reporting Service', 'core'],
        ['print_service', 'Print Service', 'production'],
        ['card_generation', 'Card Generation', 'production'],
        ['certificate_generation', 'Certificate Generation', 'production'],
        ['proof_of_payment', 'Proof of Payment Module', 'payment'],
        ['receipt_generation', 'Receipt Generation', 'payment'],
        ['paynow_gateway', 'Paynow Gateway', 'payment'],
        ['payment_initiation', 'Payment Initiation', 'payment'],
        ['payment_callback', 'Payment Callback/Webhook', 'payment'],
        ['payment_verification', 'Payment Verification', 'payment'],
        ['payment_sync', 'Payment Sync/Reconciliation', 'payment'],
        ['qr_generation', 'QR Code Generation', 'production'],
        ['notification', 'Notification Service', 'core'],
        ['search_filter', 'Search & Filter', 'core'],
        ['draft_save', 'Draft Save Module', 'core'],
        ['database', 'Database', 'infrastructure'],
        ['vps_cpu', 'VPS CPU', 'vps'],
        ['vps_memory', 'VPS Memory', 'vps'],
        ['vps_disk', 'VPS Disk', 'vps'],
        ['vps_uptime', 'VPS Uptime & Load', 'vps'],
    ];

    public function handle(): int
    {
        $checks = $this->checks();

        foreach ($this->moduleDefinitions() as [$moduleName, $displayName, $category]) {
            $check = $checks[$moduleName] ?? [
                'status' => 'unknown',
                'cause' => 'No health probe is configured for this module.',
                'meta' => [],
            ];

            $startedAt = microtime(true);
            try {
                $result = is_callable($check) ? $check() : $check;
                $status = $result['status'] ?? 'unknown';
                $cause = $result['cause'] ?? null;
                $meta = $result['meta'] ?? [];
            } catch (\Throwable $exception) {
                $status = 'critical';
                $cause = $exception->getMessage();
                $meta = ['exception' => class_basename($exception)];
            }

            $responseTime = (int) round((microtime(true) - $startedAt) * 1000);
            $existing = ModuleHealthStatus::where('module_name', $moduleName)->first();

            ModuleHealthStatus::updateOrCreate(
                ['module_name' => $moduleName],
                [
                    'display_name' => $displayName,
                    'category' => $category,
                    'status' => $status,
                    'response_state' => $status === 'healthy' ? 'available' : $status,
                    'affected_users' => 0,
                    'probable_cause' => $cause,
                    'last_checked_at' => now(),
                    'status_changed_at' => !$existing || $existing->status !== $status ? now() : $existing->status_changed_at,
                    'meta' => array_merge($meta, ['response_time_ms' => $responseTime]),
                ]
            );

            ServiceHeartbeat::create([
                'service_name' => $moduleName,
                'status' => $status,
                'response_time_ms' => $responseTime,
                'error_message' => $cause,
                'meta' => $meta,
                'checked_at' => now(),
            ]);

            $this->syncIncident($moduleName, $displayName, $status, $cause);

            $this->line("{$moduleName}: {$status} ({$responseTime} ms)");
        }

        try {
            if (Cache::store('file')->add('system.monitor.prune-old-heartbeats', true, now()->addDay())) {
                ServiceHeartbeat::where('checked_at', '<', now()->subDays(30))->delete();
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return self::SUCCESS;
    }

    private function syncIncident(string $module, string $displayName, string $status, ?string $cause): void
    {
        $latestIncident = SystemIncident::query()
            ->where('source', 'auto')
            ->where('module', $module)
            ->latest('id')
            ->first();
        $activeIncident = $latestIncident && in_array($latestIncident->status, ['open', 'in_progress'], true)
            ? $latestIncident
            : null;

        if (in_array($status, ['critical', 'offline', 'warning'], true)) {
            if ($latestIncident?->status === 'dismissed') {
                return;
            }

            $severity = in_array($status, ['critical', 'offline'], true) ? 'critical' : 'warning';
            $wasCreated = ! $activeIncident;
            $oldSeverity = $activeIncident?->severity;
            $wasEscalated = $oldSeverity !== 'critical' && $severity === 'critical';

            $incident = $activeIncident ?? new SystemIncident([
                'title' => $displayName . ' health check alert',
                'module' => $module,
                'source' => 'auto',
                'status' => 'open',
            ]);
            $incident->severity = $severity;
            $incident->description = $cause ?: "The {$displayName} health check reported {$status}.";
            $incidentMeta = array_merge($incident->meta ?? [], [
                'health_status' => $status,
                'last_health_check_at' => now()->toIso8601String(),
            ]);
            if ($wasEscalated) {
                $incidentMeta['email_sent_at'] = null;
            }
            $incident->meta = $incidentMeta;
            $incident->save();

            $meta = $incident->meta ?? [];
            $lastAttempt = isset($meta['email_last_attempt_at'])
                ? \Illuminate\Support\Carbon::parse($meta['email_last_attempt_at'])
                : null;
            $retryDue = ! $lastAttempt || $lastAttempt->lte(now()->subMinutes(5));
            $newSeverityNeedsEmail = $wasEscalated || ($wasCreated && $retryDue);

            if ($newSeverityNeedsEmail || ($retryDue && empty($meta['email_sent_at']))) {
                $meta['email_last_attempt_at'] = now()->toIso8601String();
                $incident->meta = $meta;
                $incident->save();

                if (app(SystemAlertNotifier::class)->send($incident, $wasEscalated ? 'escalated' : 'detected')) {
                    $meta['email_sent_at'] = now()->toIso8601String();
                    $meta['email_last_notified_severity'] = $severity;
                    $incident->meta = $meta;
                    $incident->save();
                }
            }

            return;
        }

        if ($status === 'healthy' && $activeIncident) {
            $meta = $activeIncident->meta ?? [];
            $activeIncident->status = 'resolved';
            $activeIncident->resolved_at = now();
            $activeIncident->resolution_notes = 'Automatically resolved after a successful health check.';
            $meta['recovered_at'] = now()->toIso8601String();
            $activeIncident->meta = $meta;
            $activeIncident->save();

            if (! empty($meta['email_last_notified_severity'])) {
                app(SystemAlertNotifier::class)->send($activeIncident, 'recovered');
            }
        }
    }

    private function checks(): array
    {
        $storageCheck = function (): array {
            $disk = Storage::disk('public');
            $probe = 'health/probe_' . uniqid('', true) . '.txt';
            $disk->put($probe, 'ok');
            $disk->delete($probe);

            return ['status' => 'healthy', 'meta' => ['disk' => 'public']];
        };

        $databaseCheck = function (): array {
            DB::select('SELECT 1');

            $meta = ['driver' => DB::getDriverName()];
            $status = 'healthy';
            $cause = null;

            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                $databaseName = DB::getDatabaseName();
                $size = DB::selectOne(
                    'SELECT COALESCE(SUM(data_length + index_length), 0) AS size_bytes FROM information_schema.tables WHERE table_schema = ?',
                    [$databaseName]
                );
                $sizeBytes = (int) ($size->size_bytes ?? 0);
                $meta['database_size_bytes'] = $sizeBytes;
                $meta['database_size_mb'] = round($sizeBytes / 1024 ** 2, 1);

                // @@datadir belongs to the MySQL host. Only inspect it when this
                // app can see the same local filesystem; remote DB paths are not
                // meaningful on the application server.
                $connection = config('database.connections.' . DB::getDefaultConnection(), []);
                $databaseHost = strtolower((string) ($connection['host'] ?? 'localhost'));
                if (in_array($databaseHost, ['localhost', '127.0.0.1', '::1'], true)) {
                    $directory = DB::selectOne('SELECT @@datadir AS data_directory')->data_directory ?? null;
                    $total = $directory && is_dir($directory) ? @disk_total_space($directory) : false;
                    $free = $directory && is_dir($directory) ? @disk_free_space($directory) : false;

                    if ($total !== false && $free !== false && $total > 0) {
                        $usedPercent = round((($total - $free) / $total) * 100, 1);
                        $meta['database_filesystem_free_bytes'] = (int) $free;
                        $meta['database_filesystem_total_bytes'] = (int) $total;
                        $meta['database_filesystem_used_percent'] = $usedPercent;
                        $meta['database_filesystem_path'] = $directory;

                        $status = $this->thresholdStatus($usedPercent, 'disk');
                        $cause = $this->thresholdCause('MySQL data filesystem usage', $usedPercent, 'disk');
                    }
                }
            }

            return ['status' => $status, 'cause' => $cause, 'meta' => $meta];
        };

        $routeCheck = fn (string $routeName): array => Route::has($routeName)
            ? ['status' => 'healthy', 'meta' => ['route' => $routeName]]
            : ['status' => 'critical', 'cause' => "Required route [{$routeName}] is not registered."];

        $tableCheck = fn (string $table): array => Schema::hasTable($table)
            ? ['status' => 'healthy', 'meta' => ['table' => $table]]
            : ['status' => 'critical', 'cause' => "Required table [{$table}] is missing."];

        $viewCheck = fn (string $view): array => view()->exists($view)
            ? ['status' => 'healthy', 'meta' => ['view' => $view]]
            : ['status' => 'critical', 'cause' => "Required view [{$view}] is missing."];

        $paynowCheck = function (): array {
            if (!config('paynow.enabled', false)) {
                return ['status' => 'warning', 'cause' => 'Paynow is disabled or is not configured.'];
            }

            return ['status' => 'healthy', 'meta' => ['configured' => true]];
        };

        $qrCodeCheck = function (): array {
            if (!class_exists(\Endroid\QrCode\QrCode::class) || !class_exists(\Endroid\QrCode\Writer\PngWriter::class)) {
                return ['status' => 'critical', 'cause' => 'QR code provider is not installed.'];
            }

            $qrCode = new \Endroid\QrCode\QrCode(
                data: 'zmc-monitoring-health-check',
                errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
                size: 100,
                margin: 1,
            );
            $image = (new \Endroid\QrCode\Writer\PngWriter())->write($qrCode)->getString();

            if (!str_starts_with($image, "\x89PNG\r\n\x1a\n")) {
                return ['status' => 'critical', 'cause' => 'QR code provider did not return a valid PNG image.'];
            }

            return ['status' => 'healthy', 'meta' => ['provider' => 'endroid/qr-code']];
        };

        $notificationCheck = function (): array {
            $mailer = (string) config('mail.default');
            if (in_array($mailer, ['log', 'array', 'null', ''], true)) {
                return [
                    'status' => 'warning',
                    'cause' => "The configured mailer ({$mailer}) does not deliver external email.",
                    'meta' => ['mailer' => $mailer],
                ];
            }

            $alertNotifier = app(SystemAlertNotifier::class);
            if (! $alertNotifier->isEnabled()) {
                return [
                    'status' => 'warning',
                    'cause' => 'System alert email notifications are disabled.',
                    'meta' => ['mailer' => $mailer],
                ];
            }

            $recipientCount = count($alertNotifier->recipients());
            if ($recipientCount === 0) {
                return [
                    'status' => 'warning',
                    'cause' => 'No valid IT alert recipients are configured.',
                    'meta' => ['mailer' => $mailer, 'recipient_count' => 0],
                ];
            }

            return [
                'status' => 'healthy',
                'meta' => ['mailer' => $mailer, 'recipient_count' => $recipientCount],
            ];
        };

        $checks = [
            'applicant_portal' => fn () => $routeCheck('accreditation.home'),
            'staff_dashboard' => fn () => $routeCheck('staff.it.dashboard'),
            'authentication' => fn () => $tableCheck('users'),
            'file_upload' => $storageCheck,
            'reporting' => fn () => $viewCheck('staff.reports.index'),
            'print_service' => fn () => $viewCheck('staff.production.list'),
            'card_generation' => fn () => $viewCheck('staff.production.show'),
            'certificate_generation' => fn () => $viewCheck('staff.production.certificate_preview'),
            'proof_of_payment' => fn () => $tableCheck('payments'),
            'receipt_generation' => fn () => $tableCheck('payments'),
            'paynow_gateway' => $paynowCheck,
            'payment_initiation' => $paynowCheck,
            'payment_callback' => fn () => $routeCheck('paynow.callback'),
            'payment_verification' => fn () => $tableCheck('payments'),
            'payment_sync' => fn () => $tableCheck('payments'),
            'qr_generation' => $qrCodeCheck,
            'notification' => $notificationCheck,
            'search_filter' => fn () => $tableCheck('applications'),
            'draft_save' => fn () => $tableCheck('applications'),
            'database' => $databaseCheck,
        ];

        if ($this->vpsMonitoringEnabled()) {
            $checks = array_merge($checks, $this->vpsChecks());
        }

        return $checks;
    }

    private function moduleDefinitions(): array
    {
        $modules = self::MODULES;

        if (! $this->vpsMonitoringEnabled()) {
            return array_values(array_filter($modules, fn (array $module) => $module[2] !== 'vps'));
        }

        foreach (config('monitoring.vps.services', []) as $key => $service) {
            $modules[] = ['vps_service_' . $key, $service['label'] ?? ucfirst(str_replace('_', ' ', $key)), 'infrastructure'];
        }

        return $modules;
    }

    private function vpsMonitoringEnabled(): bool
    {
        return (bool) config('monitoring.vps.enabled', true)
            && PHP_OS_FAMILY === 'Linux'
            && is_readable('/proc/meminfo')
            && is_readable('/proc/stat');
    }

    private function vpsChecks(): array
    {
        $checks = [
            'vps_cpu' => fn () => $this->cpuCheck(),
            'vps_memory' => fn () => $this->memoryCheck(),
            'vps_disk' => fn () => $this->diskCheck(),
            'vps_uptime' => fn () => $this->uptimeCheck(),
        ];

        foreach (config('monitoring.vps.services', []) as $key => $service) {
            $checks['vps_service_' . $key] = fn () => $this->processCheck($service['matches'] ?? []);
        }

        return $checks;
    }

    private function cpuCheck(): array
    {
        $first = $this->cpuCounters();
        usleep(250000);
        $second = $this->cpuCounters();

        if (! $first || ! $second) {
            return ['status' => 'unknown', 'cause' => 'Linux CPU counters are unavailable.'];
        }

        $totalDelta = $second['total'] - $first['total'];
        $idleDelta = $second['idle'] - $first['idle'];
        if ($totalDelta <= 0) {
            return ['status' => 'unknown', 'cause' => 'Linux CPU counters did not advance during the sample.'];
        }

        $usage = round(max(0, min(100, (($totalDelta - $idleDelta) / $totalDelta) * 100)), 1);
        $loads = sys_getloadavg() ?: [0, 0, 0];
        $cpuInfo = @file_get_contents('/proc/cpuinfo') ?: '';
        preg_match_all('/^processor\s*:/m', $cpuInfo, $coreMatches);
        $cores = max(1, count($coreMatches[0]));

        return [
            'status' => $this->thresholdStatus($usage, 'cpu'),
            'cause' => $this->thresholdCause('CPU usage', $usage, 'cpu'),
            'meta' => [
                'usage_percent' => $usage,
                'display_value' => $usage . '%',
                'load_1m' => round((float) ($loads[0] ?? 0), 2),
                'load_5m' => round((float) ($loads[1] ?? 0), 2),
                'load_15m' => round((float) ($loads[2] ?? 0), 2),
                'cpu_cores' => $cores,
            ],
        ];
    }

    private function cpuCounters(): ?array
    {
        $line = @file_get_contents('/proc/stat');
        if (! $line || ! preg_match('/^cpu\s+(.+)$/m', $line, $matches)) {
            return null;
        }

        $values = array_map('intval', preg_split('/\s+/', trim($matches[1])) ?: []);
        if (count($values) < 4) {
            return null;
        }

        return [
            'total' => array_sum(array_slice($values, 0, 8)),
            'idle' => ($values[3] ?? 0) + ($values[4] ?? 0),
        ];
    }

    private function memoryCheck(): array
    {
        $contents = @file_get_contents('/proc/meminfo');
        if (! $contents || ! preg_match('/^MemTotal:\s+(\d+)\s+kB/m', $contents, $totalMatch)
            || ! preg_match('/^MemAvailable:\s+(\d+)\s+kB/m', $contents, $availableMatch)) {
            return ['status' => 'unknown', 'cause' => 'Linux memory counters are unavailable.'];
        }

        $totalKb = (int) $totalMatch[1];
        $availableKb = (int) $availableMatch[1];
        if ($totalKb <= 0) {
            return ['status' => 'unknown', 'cause' => 'Linux reported an invalid total memory value.'];
        }

        $usedKb = max(0, $totalKb - $availableKb);
        $usage = round(($usedKb / $totalKb) * 100, 1);

        return [
            'status' => $this->thresholdStatus($usage, 'memory'),
            'cause' => $this->thresholdCause('Memory usage', $usage, 'memory'),
            'meta' => [
                'usage_percent' => $usage,
                'display_value' => $usage . '%',
                'used_mb' => round($usedKb / 1024),
                'available_mb' => round($availableKb / 1024),
                'total_mb' => round($totalKb / 1024),
            ],
        ];
    }

    private function diskCheck(): array
    {
        $path = (string) config('monitoring.vps.disk_path', '/');
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);
        if ($total === false || $free === false || $total <= 0) {
            return ['status' => 'unknown', 'cause' => "Disk capacity for {$path} could not be read."];
        }

        $used = max(0, $total - $free);
        $usage = round(($used / $total) * 100, 1);

        return [
            'status' => $this->thresholdStatus($usage, 'disk'),
            'cause' => $this->thresholdCause('Disk usage', $usage, 'disk'),
            'meta' => [
                'usage_percent' => $usage,
                'display_value' => $usage . '%',
                'used_gb' => round($used / 1024 ** 3, 1),
                'free_gb' => round($free / 1024 ** 3, 1),
                'total_gb' => round($total / 1024 ** 3, 1),
                'path' => $path,
            ],
        ];
    }

    private function uptimeCheck(): array
    {
        $contents = @file_get_contents('/proc/uptime');
        $seconds = $contents ? (int) ((float) strtok($contents, ' ')) : 0;
        if ($seconds <= 0) {
            return ['status' => 'unknown', 'cause' => 'System uptime could not be read from /proc.'];
        }

        $loads = sys_getloadavg() ?: [0, 0, 0];
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $uptime = $days > 0 ? "{$days}d {$hours}h" : ($hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m");

        return [
            'status' => 'healthy',
            'meta' => [
                'display_value' => $uptime,
                'uptime_seconds' => $seconds,
                'load_1m' => round((float) ($loads[0] ?? 0), 2),
                'load_5m' => round((float) ($loads[1] ?? 0), 2),
                'load_15m' => round((float) ($loads[2] ?? 0), 2),
            ],
        ];
    }

    private function processCheck(array $matches): array
    {
        $running = [];
        $procEntries = @scandir('/proc') ?: [];

        foreach ($procEntries as $entry) {
            if (! ctype_digit($entry)) {
                continue;
            }

            $base = '/proc/' . $entry;
            $processName = @file_get_contents($base . '/comm');
            $commandLine = @file_get_contents($base . '/cmdline');
            $processName = strtolower(trim((string) $processName));
            $commandLine = strtolower(str_replace("\0", ' ', (string) $commandLine));

            foreach ($matches as $needle) {
                $needle = strtolower((string) $needle);
                if ($needle === '') {
                    continue;
                }

                if (str_starts_with($needle, 'comm:')) {
                    $matched = str_contains($processName, substr($needle, 5));
                } elseif (str_starts_with($needle, 'cmd:')) {
                    $matched = str_contains($commandLine, substr($needle, 4));
                } else {
                    $matched = str_contains($processName . ' ' . $commandLine, $needle);
                }

                if ($matched) {
                    $running[$entry] = true;
                    break;
                }
            }
        }

        $count = count($running);

        return [
            'status' => $count > 0 ? 'healthy' : 'critical',
            'cause' => $count > 0 ? null : 'No matching process was found on the VPS.',
            'meta' => ['process_count' => $count],
        ];
    }

    private function thresholdStatus(float $usage, string $resource): string
    {
        $critical = (float) config("monitoring.vps.{$resource}_critical_percent");
        $warning = (float) config("monitoring.vps.{$resource}_warning_percent");

        return $usage >= $critical ? 'critical' : ($usage >= $warning ? 'warning' : 'healthy');
    }

    private function thresholdCause(string $label, float $usage, string $resource): ?string
    {
        $status = $this->thresholdStatus($usage, $resource);
        if ($status === 'healthy') {
            return null;
        }

        $threshold = (int) config("monitoring.vps.{$resource}_{$status}_percent");

        return "{$label} is {$usage}% (configured {$status} threshold: {$threshold}%).";
    }
}
