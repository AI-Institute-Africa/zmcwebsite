<?php

namespace App\Console\Commands;

use App\Models\ApplicationDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillDocumentBytes extends Command
{
    protected $signature = 'documents:backfill-bytes {--dry-run : Show what would change without writing}';
    protected $description = 'Read on-disk uploaded files and store their bytes into application_documents.file_data so they survive deploys';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $query = ApplicationDocument::query()
            ->whereNotNull('file_path')
            ->whereNull('file_data');

        $total = (clone $query)->count();
        $this->info("Documents to backfill: {$total}");

        $migrated = 0;
        $missing  = 0;

        $query->orderBy('id')->chunkById(100, function ($docs) use (&$migrated, &$missing, $dry) {
            foreach ($docs as $doc) {
                $bytes = $this->readBytes($doc->file_path);
                if ($bytes === null) {
                    $missing++;
                    $this->line("  MISSING #{$doc->id} {$doc->file_path}");
                    continue;
                }

                if (!$dry) {
                    $doc->storeBinary($bytes);
                }
                $migrated++;
            }
        });

        $this->info("Migrated: {$migrated}");
        $this->warn("Missing on disk: {$missing}");
        if ($dry) $this->comment('Dry run — no changes written.');

        return self::SUCCESS;
    }

    private function readBytes(string $path): ?string
    {
        foreach (['public', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return Storage::disk($disk)->get($path);
                }
            } catch (\Throwable $e) {}
        }

        $candidates = [
            storage_path('app/public/' . ltrim($path, '/')),
            storage_path('app/' . ltrim($path, '/')),
            public_path('storage/' . ltrim($path, '/')),
            public_path(ltrim($path, '/')),
            base_path(ltrim($path, '/')),
        ];
        foreach ($candidates as $abs) {
            if (is_file($abs) && is_readable($abs)) {
                return @file_get_contents($abs) ?: null;
            }
        }

        return null;
    }
}
