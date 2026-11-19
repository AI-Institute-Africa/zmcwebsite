<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'application_id',
        'owner_id',
        'doc_type',
        'file_path',
        'thumbnail_path',
        'original_name',
        'mime',
        'size',
        'sha256',
        'status',
        'review_notes',
        'replaced_by_id',
        'replaces_id',
        'version',
        'is_current',
        'superseded_reason',
        'superseded_at',
    ];

    protected $hidden = ['file_data'];

    /**
     * Persist binary file bytes into the `file_data` column safely.
     *
     * PostgreSQL bytea columns require hex encoding and server-side decode.
     * MySQL BLOB columns accept the binary value directly.
     */
    public function storeBinary(?string $bytes): void
    {
        if ($this->id === null) {
            throw new \LogicException('Model must be saved before storing binary file_data.');
        }

        if ($bytes === null || $bytes === '') {
            \DB::table($this->getTable())
                ->where('id', $this->id)
                ->update(['file_data' => null]);

            return;
        }

        if (\DB::connection()->getDriverName() === 'pgsql') {
            \DB::statement(
                'update '.$this->getTable().' set file_data = decode(?, ?) where id = ?',
                [bin2hex($bytes), 'hex', $this->id]
            );
        } else {
            \DB::table($this->getTable())
                ->where('id', $this->id)
                ->update(['file_data' => $bytes]);
        }
    }

    /**
     * Schedule the bytea backup of an uploaded file to run AFTER the HTTP
     * response has been sent to the client (Laravel `defer()`).
     *
     * Use this on user-facing submit/draft paths so applicants do not wait
     * for tens of MB of hex-encoded binary to travel over the WAN to Neon
     * before they see "Application submitted successfully".
     *
     * The file is read from the saved disk path (after `$file->store(...)`),
     * not held in memory across the request, which keeps peak memory low.
     */
    public function storeBinaryDeferredFromPath(string $relativePath, string $disk = 'public'): void
    {
        if ($this->id === null) {
            throw new \LogicException('Model must be saved before deferring binary backup.');
        }

        $id = $this->id;
        $table = $this->getTable();

        \defer(function () use ($id, $table, $relativePath, $disk) {
            // Read the file from disk (post-response). The file should exist
            // because $file->store() ran synchronously before defer() returned.
            $bytes = null;
            try {
                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($relativePath)) {
                    $bytes = \Illuminate\Support\Facades\Storage::disk($disk)->get($relativePath);
                }
            } catch (\Throwable $e) {
                \Log::warning('Deferred bytea backup: storage read failed', [
                    'doc_id' => $id, 'path' => $relativePath, 'disk' => $disk,
                    'error' => $e->getMessage(),
                ]);

                return;
            }

            if ($bytes === null || $bytes === '') {
                \Log::warning('Deferred bytea backup: file missing on disk', [
                    'doc_id' => $id, 'path' => $relativePath, 'disk' => $disk,
                ]);

                return;
            }

            $isPostgres = \DB::connection()->getDriverName() === 'pgsql';
            // Retry the bytea write a few times to survive transient Neon
            // network blips. After exhausting retries we log; the next deploy
            // will pick the row up via documents:backfill-bytes if the file
            // is still on disk on a container that runs the build step.
            $maxAttempts = 3;
            if (!$isPostgres) {
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    try {
                        \DB::table($table)->where('id', $id)->update(['file_data' => $bytes]);

                        return;
                    } catch (\Throwable $e) {
                        if ($attempt === $maxAttempts) {
                            \Log::warning('Deferred bytea backup failed after retries', [
                                'doc_id' => $id,
                                'attempts' => $maxAttempts,
                                'error' => $e->getMessage(),
                            ]);

                            return;
                        }
                        usleep(250_000 * $attempt);
                    }
                }
            } else {
                $hex = bin2hex($bytes);
                $sql = "update {$table} set file_data = decode(?, ?) where id = ?";
                $params = [$hex, 'hex', $id];
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    try {
                        \DB::statement($sql, $params);

                        return;
                    } catch (\Throwable $e) {
                        if ($attempt === $maxAttempts) {
                            \Log::warning('Deferred bytea backup failed after retries', [
                                'doc_id' => $id,
                                'attempts' => $maxAttempts,
                                'error' => $e->getMessage(),
                            ]);

                            return;
                        }
                        // Backoff: 250ms, 750ms.
                        usleep(250_000 * $attempt);
                    }
                }
            }
        }, name: "store_binary_{$table}_{$id}");
    }

    protected $casts = [
        'is_current' => 'boolean',
        'superseded_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_id');
    }

    public function previousVersions()
    {
        return self::where('application_id', $this->application_id)
            ->where('doc_type', $this->doc_type)
            ->where('is_current', false)
            ->orderByDesc('version')
            ->get();
    }

    public function getDocumentTypeAttribute(): string
    {
        $types = [
            'id_scan' => 'National ID / Passport',
            'passport_photo' => 'Passport Photo',
            'employment_letter' => 'Employment Letter',
            'reference_letter' => 'Reference / Testimonial',
            'educational_certificate' => 'Educational Certificate',
            'passport_biodata_page' => 'Passport Bio Data Page',
            'clearance_letter' => 'Clearance Letter',
            'current_card' => 'Current Accreditation Card',
            'employer_letter' => 'Employer Letter',
            'affidavit' => 'Affidavit',
            'police_report' => 'Police Report',
            'payment_supporting' => 'Supporting Document',
        ];

        return $types[$this->doc_type] ?? ucwords(str_replace('_', ' ', $this->doc_type));
    }

    public function getUrlAttribute(): string
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $staffRoles = [
            'super_admin', 'accreditation_officer', 'accounts_payments', 'registrar',
            'production', 'it_admin', 'auditor', 'director', 'oversight',
            'pr_officer', 'public_info_compliance',
        ];
        $isStaff = $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole($staffRoles);

        try {
            if ($isStaff) {
                return route('staff.documents.show', $this);
            }

            $appType = strtolower((string) optional($this->application)->application_type);
            if (in_array($appType, ['ap4', 'media_house', 'media-house', 'mediahouse'], true)) {
                return route('mediahouse.downloads.file', ['doc' => $this->id]);
            }

            return route('accreditation.downloads.file', ['doc' => $this->id]);
        } catch (\Throwable $e) {
        }

        if ($this->file_path) {
            return asset('storage/'.$this->file_path);
        }

        return '#';
    }
}
