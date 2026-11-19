<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Support\RegionAccessGuard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentViewController extends Controller
{
    public function show(ApplicationDocument $document): \Symfony\Component\HttpFoundation\Response
    {
        // Region authorization: officers/production may only view documents
        // attached to applications inside their assigned regions.
        $application = $document->application;
        if ($application) {
            RegionAccessGuard::ensure($application, 'You are not allowed to view this document.');
        }

        $downloadName = $document->original_name ?: basename($document->file_path ?? 'document');
        $mime = $document->mime ?: 'application/octet-stream';
        $relPath = ltrim((string) $document->file_path, '/');

        $candidatePaths = [];
        if ($relPath !== '') {
            // Disk-relative candidates
            foreach (['public', 'local'] as $disk) {
                try {
                    if (Storage::disk($disk)->exists($relPath)) {
                        return Storage::disk($disk)->response($relPath, $downloadName, [
                            'Content-Type' => $mime,
                        ]);
                    }
                } catch (\Throwable $e) {
                    // disk not configured — ignore
                }
            }

            // Absolute filesystem candidates
            $candidatePaths = [
                storage_path('app/public/' . $relPath),
                storage_path('app/' . $relPath),
                public_path('storage/' . $relPath),
                public_path($relPath),
                base_path($relPath),
            ];
            foreach ($candidatePaths as $abs) {
                if (is_file($abs) && is_readable($abs)) {
                    return response()->file($abs, [
                        'Content-Type' => $mime,
                        'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
                    ]);
                }
            }
        }

        // Persistent file_data column fallback (raw bytea, written by upload controllers)
        if (\Schema::hasColumn($document->getTable(), 'file_data')) {
            $data = $document->getAttribute('file_data');
            if (is_resource($data)) {
                $data = stream_get_contents($data);
            }
            if (is_string($data) && $data !== '') {
                return response($data, 200, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
                    'Cache-Control' => 'no-cache',
                ]);
            }
        }

        Log::warning('Document file missing from storage', [
            'document_id'   => $document->id,
            'application_id'=> $document->application_id,
            'doc_type'      => $document->doc_type ?? null,
            'file_path'     => $document->file_path,
            'tried'         => $candidatePaths,
        ]);

        abort(response()->view('errors.document_missing', [
            'document'   => $document,
            'tried'      => $candidatePaths,
        ], 404));
    }
}
