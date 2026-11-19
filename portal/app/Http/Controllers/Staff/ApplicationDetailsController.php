<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApplicationDetailsController extends Controller
{
    /**
     * Region authorization guard. For region-scoped roles only
     * (accreditation_officer, production), enforce that the application's
     * collection_region matches one of the user's assigned regions. All other
     * staff roles (registrar, accounts, director, etc.) see all regions.
     */
    private function ensureCanAccessRegion(Application $application): void
    {
        $user = Auth::user();
        if (!$user) abort(403);
        if ($user->hasAnyRole(['super_admin', 'admin'])) return;

        // Only enforce for users whose ONLY scoped roles are officer/production
        $regionScoped = $user->hasAnyRole(['accreditation_officer', 'production']);
        $hasUnscopedRole = $user->hasAnyRole([
            'registrar', 'accounts_payments', 'director', 'auditor', 'it_admin',
            'complaints_officer', 'pr_officer', 'public_info_compliance', 'research_training',
        ]);
        if (!$regionScoped || $hasUnscopedRole) return;

        $regions = $user->assignedCollectionRegions();
        $appRegion = $application->collection_region ? strtolower(trim($application->collection_region)) : null;

        if (empty($regions) || !$appRegion || !in_array($appRegion, $regions, true)) {
            abort(403, 'This application is outside your assigned region(s).');
        }
    }

    /**
     * JSON details for dashboard “View” modal.
     */
    public function show(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $application->loadMissing(['applicant','documents','paymentSubmissions.verifier']);

        $fd = $application->form_data;
        if (is_string($fd)) {
            $decoded = json_decode($fd, true);
            $fd = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($fd)) $fd = [];

        $isLocal = ($application->journalist_scope === 'local') || (($fd['journalist_scope'] ?? '') === 'local');

        // Base application payload
        $app = $application->toArray();
        $app['applicant_name'] = $application->applicant?->name;
        $app['form_code'] = $this->formCode($application);
        $app['category_initials'] = ($application->application_type === 'registration')
            ? $application->media_house_category_code
            : $application->accreditation_category_code;

        if ($isLocal) {
            $foreignFields = [
                'passport_no', 'passport_expiry', 'passport_issued_at', 
                'first_time_in_zim', 'last_in_zim_when', 'address_in_zimbabwe', 
                'arrived_on', 'arrival_mode', 'port_of_entry', 
                'departing_on', 'special_assignment', 'journalist_based_country'
            ];
            foreach ($foreignFields as $f) {
                unset($fd[$f], $app[$f]);
            }
        }

        // Flatten common AP3 fields for convenience (if present)
        foreach ([
            'title','first_name','surname','other_names','dob','sex','birth_place','origin','nationality',
            'id_passport_number','employer_name','medium_type','designation','assignment_brief','arrival_date',
            'departure_date','port_entry','zim_local_address','zim_address', 'past_work_links'
        ] as $k) {
            if (array_key_exists($k, $fd) && !array_key_exists($k, $app)) {
                $app[$k] = $fd[$k];
            }
        }

        // AP1 convenience blocks (if stored under ap1 / directors / managers)
        $ap1 = [];
        if (isset($fd['ap1']) && is_array($fd['ap1'])) {
            $ap1 = $fd['ap1'];
        } else {
            // Some builds store AP1 keys at top-level
            $possible = ['category','service_name','operating_model','org_name','reg_no','website','head_office','postal_address',
                'contact_person','contact_email','contact_phone'];
            $hasAny = false;
            foreach ($possible as $p) {
                if (array_key_exists($p, $fd)) { $hasAny = true; break; }
            }
            if ($hasAny) {
                foreach ($possible as $p) {
                    if (array_key_exists($p, $fd)) $ap1[$p] = $fd[$p];
                }
            }
        }

        $directors = [];
        if (isset($fd['directors']) && is_array($fd['directors'])) $directors = $fd['directors'];
        if (isset($fd['directors_rows']) && is_array($fd['directors_rows'])) $directors = $fd['directors_rows'];

        $managers = [];
        if (isset($fd['managers']) && is_array($fd['managers'])) $managers = $fd['managers'];
        if (isset($fd['managers_rows']) && is_array($fd['managers_rows'])) $managers = $fd['managers_rows'];

        $documents = $application->documents->map(function ($d) {
            $type = $d->doc_type ?? $d->document_type ?? 'document';
            if (str_starts_with($type, 'past_work_sample_')) {
                $type = 'Past Work Sample (' . str_replace('past_work_sample_', '', $type) . ')';
            }
            return [
                'document_type' => $type,
                'original_name' => $d->original_name ?? null,
                'file_name'     => $d->file_path ?? null,
                'url'           => !empty($d->file_path) ? $d->url : null,
                'status'        => $d->status ?? null,
            ];
        })->values();

        // Previous applications
        $previousApplications = Application::where('applicant_id', $application->applicant_id)
            ->where('id', '!=', $application->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'reference', 'application_type', 'status', 'created_at'])
            ->map(function($pa) {
                return [
                    'id' => $pa->id,
                    'reference' => $pa->reference,
                    'type' => $pa->application_type,
                    'status' => $pa->status,
                    'date' => $pa->created_at?->format('d M Y'),
                ];
            });

        // Previous payments
        $previousPayments = Payment::where('payer_user_id', $application->user_id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'reference', 'amount', 'currency', 'method', 'status', 'created_at'])
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'reference' => $p->reference,
                    'amount' => $p->amount,
                    'currency' => $p->currency,
                    'method' => $p->method,
                    'status' => $p->status,
                    'date' => $p->created_at?->format('d M Y'),
                ];
            });

        // ----------------------------------------------------------------
        // Payment block — surfaces submission method, paynow ref, proof file,
        // and any PaymentSubmission rows so Accounts staff can verify payment
        // directly from the dashboard "View" modal.
        // ----------------------------------------------------------------

        // Normalize a stored file path: strip leading slash plus any stale
        // "public/" or "storage/" prefixes, and reject obviously-bad input
        // (other URL schemes, traversal segments).
        $normalizePath = function (?string $path): ?string {
            if (!$path) return null;
            $p = trim($path);
            if ($p === '') return null;
            if (preg_match('#^https?://#i', $p)) return $p; // pass-through
            if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $p)) return null;
            $rel = ltrim($p, '/');
            if (str_starts_with($rel, 'public/'))  $rel = substr($rel, 7);
            if (str_starts_with($rel, 'storage/')) $rel = substr($rel, 8);
            foreach (explode('/', $rel) as $seg) {
                if ($seg === '..' || $seg === '.') return null;
            }
            return $rel;
        };

        // Locate an ApplicationDocument on this application whose file_path
        // matches the given proof/waiver path. We use these rows because they
        // carry a bytea backup (file_data) and the staff.documents.show route
        // serves disk-or-bytea, surviving autoscale container recycling where
        // the local /storage/... copy may be missing.
        $documentByPath = function (?string $path) use ($application, $normalizePath): ?\App\Models\ApplicationDocument {
            $rel = $normalizePath($path);
            if (!$rel || preg_match('#^https?://#i', $rel)) return null;
            return $application->documents->first(function ($d) use ($rel) {
                $stored = ltrim((string)($d->file_path ?? ''), '/');
                if ($stored === '') return false;
                if (str_starts_with($stored, 'public/'))  $stored = substr($stored, 7);
                if (str_starts_with($stored, 'storage/')) $stored = substr($stored, 8);
                return $stored === $rel;
            });
        };

        $proofUrlFor = function (?string $path) use ($normalizePath, $documentByPath): ?string {
            $rel = $normalizePath($path);
            if ($rel === null) return null;
            // Pass-through absolute URLs.
            if (preg_match('#^https?://#i', $rel)) return $rel;

            // Prefer the bytea-backed staff documents route when we have a
            // matching ApplicationDocument row. This survives autoscale
            // container recycling because DocumentViewController falls back
            // to the file_data bytea column when the disk copy is missing.
            $doc = $documentByPath($path);
            if ($doc) {
                try { return $doc->url; } catch (\Throwable $e) { /* fall through */ }
            }

            // Legacy fallback: direct /storage/... asset URL. Works only when
            // the local disk on the serving container still has the file.
            return asset('storage/' . $rel);
        };

        // Legacy fallback: some flows store the proof as an ApplicationDocument
        // with doc_type = 'proof_of_payment' instead of payment_proof_path.
        $legacyProofDoc = $application->documents
            ->first(fn ($d) => in_array(
                $d->doc_type ?? $d->document_type ?? '',
                ['proof_of_payment', 'payment_proof'],
                true
            ));

        // Same legacy fallback for waivers, in case the row was mirrored as
        // an ApplicationDocument but waiver_path on the application was not set.
        $legacyWaiverDoc = $application->documents
            ->first(fn ($d) => in_array(
                $d->doc_type ?? $d->document_type ?? '',
                ['payment_waiver', 'waiver'],
                true
            ));

        $primaryProofPath = $application->payment_proof_path
            ?: ($legacyProofDoc?->file_path);
        $primaryProofUrl  = $proofUrlFor($primaryProofPath);

        $primaryWaiverPath = $application->waiver_path
            ?: ($legacyWaiverDoc?->file_path);
        $primaryWaiverUrl  = $proofUrlFor($primaryWaiverPath);

        $submissions = $application->paymentSubmissions->map(function ($s) use ($proofUrlFor) {
            return [
                'id'             => $s->id,
                'stage'          => $s->payment_stage,
                'method'         => $s->method,
                'reference'      => $s->reference,
                'amount'         => $s->amount,
                'currency'       => $s->currency,
                'status'         => $s->status,
                'submitted_at'   => optional($s->submitted_at)->toDateTimeString(),
                'verified_at'    => optional($s->verified_at)->toDateTimeString(),
                'verifier_name'  => $s->verifier?->name,
                'rejection_reason' => $s->rejection_reason,
                'proof_path'     => $s->proof_path,
                'proof_url'      => $proofUrlFor($s->proof_path),
                'waiver_path'    => $s->waiver_path,
                'waiver_url'     => $proofUrlFor($s->waiver_path),
            ];
        })->values();

        $payment = [
            'submission_method'  => $application->payment_submission_method,
            'paynow_reference'   => $application->paynow_reference,
            'payment_status'     => $application->payment_status,
            'proof_status'       => $application->proof_status ?? null,
            'proof_path'         => $primaryProofPath,
            'proof_url'          => $primaryProofUrl,
            'proof_uploaded_at'  => optional($application->payment_proof_uploaded_at)->toDateTimeString(),
            'waiver_path'        => $primaryWaiverPath,
            'waiver_url'         => $primaryWaiverUrl,
            'waiver_status'      => $application->waiver_status ?? null,
            'paid_at'            => optional($application->payment_paid_at ?? null)->toDateTimeString(),
            'receipt_number'     => $application->receipt_number ?? null,
            'submissions'        => $submissions,
        ];

        // Payment correspondence shown in the modal is intentionally limited to
        // the applicant-visible thread. Internal staff-only payment notes are
        // NOT included here, because this same endpoint serves several staff
        // roles (officer, registrar, production, accounts) and we don't want
        // to leak internal accounts notes outside the accounts team.
        $paymentComments = $application->comments()
            ->where('comment_type', \App\Models\ApplicationComment::TYPE_PAYMENT)
            ->where('visibility', \App\Models\ApplicationComment::VISIBILITY_APPLICANT)
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($c) {
                return [
                    'id'         => $c->id,
                    'body'       => $c->body,
                    'visibility' => $c->visibility,
                    'user_name'  => $c->user?->name ?? 'Staff',
                    'user_role'  => $c->user_role,
                    'created_at' => optional($c->created_at)->toDateTimeString(),
                    'created_human' => optional($c->created_at)->diffForHumans(),
                ];
            });

        // Only the Accounts team is currently authorised to message the
        // applicant about payment/proof issues from this modal. Routes already
        // restrict /staff/accounts to `accounts_payments`, but we also gate at
        // the data layer so other roles can't repurpose the endpoint.
        $user = Auth::user();
        $activeRole = session('active_staff_role');
        $canPostPaymentMessage = $user && $activeRole === 'accounts_payments';

        return response()->json([
            'ok' => true,
            'application' => $app,
            'form_data' => $fd,
            'ap1' => $ap1,
            'directors' => $directors,
            'managers' => $managers,
            'documents' => $documents,
            'payment' => $payment,
            'payment_comments' => $paymentComments,
            'can_post_payment_message' => $canPostPaymentMessage,
            'previous_applications' => $previousApplications,
            'previous_payments' => $previousPayments,
        ]);
    }

    /**
     * Global search for staff: redirects to the specific application if found by ref, 
     * otherwise falls back to the officer's filtered list.
     */
    public function globalSearch(Request $request)
    {
        $q = $request->query('q');
        if (!$q) return back();

        // 1. Check for exact reference
        $app = Application::where('reference', trim($q))->first();

        // Partial reference (e.g. the last digits): jump straight to it when it is unambiguous.
        // The region/role checks below still apply to the match.
        if (!$app && mb_strlen(trim($q)) >= 4) {
            $like = '%' . addcslashes(trim($q), '%_\\') . '%';
            $partial = Application::where('reference', 'like', $like)->limit(2)->get();
            if ($partial->count() === 1) {
                $app = $partial->first();
            }
        }
        if ($app) {
            // Region authorization: officers/production may only land on
            // applications inside their assigned regions.
            $user = Auth::user();
            $isPrivileged = $user && $user->hasAnyRole(['super_admin', 'admin']);
            $regionScoped = $user && $user->hasAnyRole(['accreditation_officer', 'production']);
            $hasUnscoped = $user && $user->hasAnyRole([
                'registrar', 'accounts_payments', 'director', 'auditor', 'it_admin',
                'complaints_officer', 'pr_officer', 'public_info_compliance', 'research_training',
            ]);
            if (!$isPrivileged && $regionScoped && !$hasUnscoped) {
                $regions = $user->assignedCollectionRegions();
                $appRegion = $app->collection_region ? strtolower(trim($app->collection_region)) : null;
                if (empty($regions) || !$appRegion || !in_array($appRegion, $regions, true)) {
                    return back()->with('error', 'No application found in your assigned region(s) for that reference.');
                }
            }

            $role = session('active_staff_role');
            // Redirect based on role if possible
            $route = match($role) {
                'accreditation_officer' => 'staff.officer.applications.show',
                'registrar' => 'staff.registrar.applications.show',
                'accounts_payments' => 'staff.accounts.applications.show',
                'production' => 'staff.production.applications.show',
                default => 'staff.officer.dashboard'
            };
            
            // If the route doesn't exist for some reason, fallback to officer dashboard
            try {
                return redirect()->route($route, $app->id);
            } catch (\Exception $e) {
                return redirect()->route('staff.officer.dashboard');
            }
        }

        // 2. Fallback to officer application list (which has a robust search)
        return redirect()->route('staff.officer.applications.index', ['q' => $q]);
    }

    private function formCode(Application $application): string
    {
        if ($application->application_type === 'accreditation') {
            return 'AP3';
        }
        if ($application->application_type === 'registration') {
            return $application->form_type ?? 'AP1';
        }
        return strtoupper((string)$application->application_type);
    }
}
