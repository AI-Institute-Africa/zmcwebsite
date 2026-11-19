<?php

namespace App\Support;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors a payment proof, waiver, or supporting file into an
 * `application_documents` row so the file survives autoscale container
 * recycling.
 *
 * Background: payment_proof_path / waiver_path on the `applications` table
 * point at files in storage/app/public, which on Replit autoscale is local
 * to one container instance. When a different container later tries to serve
 * /storage/payment_proofs/... it returns 404 because the file is gone.
 *
 * The mirror writes a row into application_documents (which has a bytea
 * `file_data` backup column), then queues the bytea backup via the existing
 * deferred helper on the model so the applicant doesn't wait on the upload
 * to Neon. Once the row exists, ApplicationDetailsController prefers the
 * bytea-backed `staff.documents.show` route, which falls back to file_data
 * when the local disk copy is missing.
 */
class PaymentProofMirror
{
    public static function mirror(
        Application $application,
        string $relativePath,
        string $docType,
        ?string $originalName = null,
        ?string $mime = null,
        ?int $size = null,
        ?string $sha256 = null
    ): ?ApplicationDocument {
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '') {
            return null;
        }

        try {
            $doc = $application->documents()->create([
                'doc_type'      => $docType,
                'file_path'     => $relativePath,
                'original_name' => $originalName,
                'mime'          => $mime,
                'size'          => $size,
                'sha256'        => $sha256,
                'status'        => 'pending',
                'is_current'    => true,
                'version'       => 1,
                'owner_id'      => Auth::id() ?: ($application->applicant_user_id ?: $application->user_id ?? null),
            ]);

            $doc->storeBinaryDeferredFromPath($relativePath, 'public');

            return $doc;
        } catch (\Throwable $e) {
            // A mirror failure must never break the upload flow itself.
            Log::warning('PaymentProofMirror::mirror failed', [
                'application_id' => $application->id,
                'doc_type'       => $docType,
                'path'           => $relativePath,
                'error'          => $e->getMessage(),
            ]);
            return null;
        }
    }
}
