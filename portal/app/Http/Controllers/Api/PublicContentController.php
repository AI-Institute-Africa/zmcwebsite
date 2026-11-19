<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Notice;
use App\Models\Vacancy;
use App\Models\Tender;
use App\Models\Event;
use App\Models\Complaint;
use App\Models\WebsiteDownload;
use App\Models\SystemConfig;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PublicContentController extends Controller
{
    private function allowedOrigins(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($origin) => rtrim((string) $origin, '/'),
            (array) config('cors.allowed_origins', [])
        ))));
    }

    private function corsOrigin(): string
    {
        $origin = request()->headers->get('Origin');

        if ($origin && in_array(rtrim($origin, '/'), $this->allowedOrigins(), true)) {
            return $origin;
        }

        return $this->allowedOrigins()[0] ?? rtrim((string) config('app.website_url'), '/');
    }

    private function corsJson($data, $status = 200)
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin', $this->corsOrigin())
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Idempotency-Key')
            ->header('Vary', 'Origin');
    }

    public function preflight()
    {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', $this->corsOrigin())
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Idempotency-Key')
            ->header('Vary', 'Origin');
    }

    private function addFileUrls($item)
    {
        $storageUrl = asset('storage');
        $arr = $item->toArray();

        if ($item instanceof News) {
            $paths = array_values(array_unique(array_filter(array_merge((array) ($arr['image_paths'] ?? []), [$arr['image_path'] ?? null]))));
            $arr['image_paths'] = $paths;
            $arr['image_urls'] = array_map(fn ($path) => $storageUrl . '/' . $path, $paths);
            $arr['image_url'] = $arr['image_urls'][0] ?? null;
            if (strcasecmp((string) ($arr['category'] ?? ''), 'Statement') === 0) {
                $arr['category'] = 'Press Release';
            }
        }

        if ($item instanceof Vacancy) {
            foreach ([
                'salary_range', 'salary_display', 'reports_to', 'supervises',
                'application_requirements', 'interview_dates', 'expected_start_date',
                'shortlisting_criteria', 'submission_method', 'submission_address',
            ] as $privateOrRemovedField) {
                unset($arr[$privateOrRemovedField]);
            }
            $arr['vacancy_status'] = $this->vacancyStatus($arr['closing_at'] ?? null);
        }

        if (!empty($arr['image_path'])) {
            $arr['image_url'] = $storageUrl . '/' . $arr['image_path'];
        }
        if (!empty($arr['attachment_path'])) {
            $arr['attachment_url'] = $storageUrl . '/' . $arr['attachment_path'];
        }
        if (!empty($arr['file_path'])) {
            $arr['file_url'] = $storageUrl . '/' . $arr['file_path'];
        }
        return $arr;
    }

    private function vacancyStatus(?string $closingAt): string
    {
        if (!$closingAt) return 'Open';
        $closingDate = \Illuminate\Support\Carbon::parse($closingAt)->startOfDay();
        if ($closingDate->lt(now()->startOfDay())) return 'Closed';
        if ($closingDate->lte(now()->addDays(7)->startOfDay())) return 'Closing Soon';
        return 'Open';
    }

    private function formatCollection($items)
    {
        return $items->map(fn($item) => $this->addFileUrls($item))->values();
    }

    public function getContent(Request $request)
    {
        $news = News::where('is_published', true)
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        $notices = Notice::where('is_published', true)
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        $vacancies = Vacancy::where('is_published', true)
            ->where(function($q) {
                $q->whereNull('closing_at')->orWhere('closing_at', '>=', now()->startOfDay());
            })
            ->orderByDesc('published_at')
            ->get();

        $tenders = Tender::where('is_published', true)
            ->where(function($q) {
                $q->whereNull('closing_at')->orWhere('closing_at', '>=', now()->startOfDay());
            })
            ->orderByDesc('published_at')
            ->get();

        $events = Event::where('is_published', true)
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get();

        return $this->corsJson([
            'status' => 'success',
            'data' => [
                'news'      => $this->formatCollection($news),
                'notices'   => $this->formatCollection($notices),
                'vacancies' => $this->formatCollection($vacancies),
                'tenders'   => $this->formatCollection($tenders),
                'events'    => $this->formatCollection($events),
            ],
            'meta' => [
                'base_url'    => url('/'),
                'storage_url' => asset('storage/'),
                'timestamp'   => now()->toIso8601String(),
            ]
        ]);
    }

    public function getNews()
    {
        $news = News::where('is_published', true)
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        return $this->corsJson([
            'status' => 'success',
            'data' => $this->formatCollection($news),
        ]);
    }

    public function getNotices()
    {
        $notices = Notice::where('is_published', true)
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        return $this->corsJson([
            'status' => 'success',
            'data' => $this->formatCollection($notices),
        ]);
    }

    public function getEvents()
    {
        $events = Event::where('is_published', true)
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get();

        return $this->corsJson([
            'status' => 'success',
            'data' => $this->formatCollection($events),
        ]);
    }

    public function getVacancies()
    {
        $vacancies = Vacancy::where('is_published', true)
            ->where(function($q) {
                $q->whereNull('closing_at')->orWhere('closing_at', '>=', now()->startOfDay());
            })
            ->orderByDesc('published_at')
            ->get();

        return $this->corsJson([
            'status' => 'success',
            'data' => $this->formatCollection($vacancies),
        ]);
    }

    public function getTenders()
    {
        $tenders = Tender::where('is_published', true)
            ->where(function($q) {
                $q->whereNull('closing_at')->orWhere('closing_at', '>=', now()->startOfDay());
            })
            ->orderByDesc('published_at')
            ->get();

        return $this->corsJson([
            'status' => 'success',
            'data' => $this->formatCollection($tenders),
        ]);
    }

    /**
     * GET /api/v1/downloads
     * Published documents & reports grouped by category.
     */
    public function getDownloads()
    {
        $downloads = WebsiteDownload::where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get();

        $grouped = $downloads->groupBy('category')->map(function ($items, $cat) {
            return [
                'category'       => $cat,
                'category_label' => $items->first()->category_label,
                'items'          => $this->formatCollection($items),
            ];
        })->values();

        return $this->corsJson([
            'status' => 'success',
            'data'   => $grouped,
        ]);
    }

    /**
     * POST /api/v1/appeals
     * Receives FOIA appeal from website (without payment).
     * Creates appeal with status 'pending_payment' and returns appeal reference.
     * User must then pay via PayNow using the appeal ID.
     */
    public function submitAppeal(Request $request)
    {
        $validated = $request->validate([
            'full_name'     => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'nullable|string|max:50',
            'subject'       => 'nullable|string|max:255',
            'message'       => 'required|string|min:10',
            'category_id'   => ['nullable', 'integer', Rule::exists('appeal_categories', 'id')->where('is_active', true)],
            'attachment'    => 'nullable|file|extensions:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $idempotencyHeader = trim((string) $request->header('Idempotency-Key', ''));
        $idempotencyKey = hash('sha256', $idempotencyHeader !== ''
            ? $idempotencyHeader
            : implode('|', [
                mb_strtolower(trim($validated['email'])),
                mb_strtolower(trim((string) ($validated['subject'] ?? ''))),
                hash('sha256', trim($validated['message'])),
                (string) intdiv(now()->timestamp, 300),
            ]));

        if ($idempotencyKey) {
            $existing = Complaint::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $this->appealSubmissionResponse($existing, 200);
            }
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $this->storePublicFile($file, 'appeals/attachments', FileType::safeHashName($file));
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = FileType::safeUploadedMime($file);
        }

        if (SystemConfig::getValue('complaints.workflow', ['acknowledgement_enabled' => true])['acknowledgement_enabled'] ?? true) {
        try {
            $appeal = DB::transaction(function () use ($validated, $attachmentPath, $attachmentName, $attachmentMime, $idempotencyKey) {
                $appeal = Complaint::create([
                    'reference_number' => 'AP-' . now()->format('Y') . '-' . Str::upper(Str::random(12)),
                    'category_id' => $validated['category_id'] ?? null,
                    'idempotency_key' => $idempotencyKey,
                    'type' => 'appeal',
                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'subject' => $validated['subject'] ?? 'FOIA Appeal',
                    'message' => $validated['message'],
                    'attachment_path' => $attachmentPath,
                    'attachment_original_name' => $attachmentName,
                    'attachment_mime' => $attachmentMime,
                    'status' => 'open',
                    'payment_status' => 'pending',
                ]);

                if (!$appeal->reference_number) {
                    $appeal->update(['reference_number' => 'AP-' . now()->format('Y') . '-' . str_pad((string) $appeal->id, 8, '0', STR_PAD_LEFT)]);
                }

                return $appeal;
            });
        } catch (\Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            // A concurrent retry can win the unique idempotency key race.
            if ($idempotencyKey && ($existing = Complaint::where('idempotency_key', $idempotencyKey)->first())) {
                return $this->appealSubmissionResponse($existing, 200);
            }

            report($exception);
            return $this->corsJson([
                'status' => 'error',
                'message' => 'Your appeal could not be saved. Please try again.',
            ], 500);
        }

        // Send the acknowledgement only after the appeal has been committed.
        // A mail failure must never make a successfully submitted appeal look
        // like it failed; it is logged for the communications team to retry.
        try {
            $telephone = (string) config('organization.foia_phone', '+263 (4) 700 991-5');
            $contactEmail = 'info@zmc.org.zw';
            Mail::raw(
                "Dear {$appeal->full_name},\n\n"
                . "Your appeal has been received and registered by the Zimbabwe Media Commission (ZMC).\n"
                . "Reference number: {$appeal->reference_number}\n"
                . "Please keep this number for follow-up. An officer will contact you in due course regarding your appeal.\n\n"
                . "For enquiries, call {$telephone} or email {$contactEmail}, quoting your reference number.\n\n"
                . "Regards,\nAppeals Desk\nZimbabwe Media Commission",
                function ($message) use ($appeal) {
                    $message->to($appeal->email)
                        ->subject('ZMC FOIA appeal received — ' . $appeal->reference_number);
                }
            );
        } catch (\Throwable $mailException) {
            report($mailException);
        }
        }

        return $this->appealSubmissionResponse($appeal, 201);
    }

    /**
     * POST /api/v1/appeals/payment/proof
     * Receives a manual proof of payment uploaded from the public website.
     */
    public function uploadAppealPaymentProof(Request $request)
    {
        $validated = $request->validate([
            'appeal_id' => ['required', 'integer', 'exists:complaints,id'],
            'payment_proof' => ['required', 'file', 'extensions:pdf,jpg,jpeg,png', 'max:10240'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $appeal = Complaint::whereKey($validated['appeal_id'])
            ->where('type', 'appeal')
            ->first();

        if (!$appeal) {
            return $this->corsJson([
                'status' => 'error',
                'message' => 'Appeal not found.',
            ], 404);
        }

        if ($appeal->payment_status === 'paid') {
            return $this->corsJson([
                'status' => 'error',
                'message' => 'This appeal has already been paid.',
            ], 409);
        }

        $file = $request->file('payment_proof');
        $path = null;

        try {
            $path = $this->storePublicFile($file, 'appeals/payment-proofs', FileType::safeHashName($file));

            if ($appeal->payment_proof_path) {
                Storage::disk('public')->delete($appeal->payment_proof_path);
            }

            $appeal->update([
                'payment_proof_path' => $path,
                'payment_proof_original_name' => $file->getClientOriginalName(),
                'payment_proof_mime' => FileType::safeUploadedMime($file),
                'payment_ref' => $validated['payment_reference'] ?? $appeal->payment_ref,
                'payment_status' => 'pending',
            ]);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            report($exception);
            return $this->corsJson([
                'status' => 'error',
                'message' => 'Proof of payment could not be uploaded. Please try again.',
            ], 500);
        }

        return $this->corsJson([
            'status' => 'success',
            'message' => 'Proof of payment uploaded successfully.',
            'appeal_id' => $appeal->id,
            'reference' => $appeal->reference_number,
            'payment_status' => $appeal->payment_status,
        ], 201);
    }

    private function appealSubmissionResponse(Complaint $appeal, int $httpStatus)
    {
        return $this->corsJson([
            'status' => 'success',
            'message' => 'Appeal received. Complete payment to proceed.',
            'appeal_id' => $appeal->id,
            'reference' => $appeal->reference_number,
            'payment_required' => true,
            'payment_status' => $appeal->payment_status,
            'amount' => (float) config('fees.foia.appeal_fee'),
        ], $httpStatus);
    }

    // ─── Single record endpoints ──────────────────────────────────────────────

    public function showNotice($id)
    {
        $notice = Notice::where('is_published', true)->findOrFail($id);
        return $this->corsJson(['status' => 'success', 'data' => $this->addFileUrls($notice)]);
    }

    public function showEvent($id)
    {
        $event = Event::where('is_published', true)->findOrFail($id);
        return $this->corsJson(['status' => 'success', 'data' => $this->addFileUrls($event)]);
    }

    public function showNews($id)
    {
        $news = News::where('is_published', true)->findOrFail($id);
        return $this->corsJson(['status' => 'success', 'data' => $this->addFileUrls($news)]);
    }

    public function showVacancy($id)
    {
        $vacancy = Vacancy::where('is_published', true)->findOrFail($id);
        return $this->corsJson(['status' => 'success', 'data' => $this->addFileUrls($vacancy)]);
    }

    public function showTender($id)
    {
        $tender = Tender::where('is_published', true)->findOrFail($id);
        return $this->corsJson(['status' => 'success', 'data' => $this->addFileUrls($tender)]);
    }

    // ─── Submit Public Complaint ──────────────────────────────────────────────
    public function submitComplaint(Request $request)
    {
        $validated = $request->validate([
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'subject'    => 'nullable|string|max:255',
            'message'    => 'required|string|min:10',
            'attachment' => 'nullable|file|extensions:pdf,doc,docx,jpg,jpeg,png,mp3,mp4,avi,mov,wav,webm|max:20480',
        ]);

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $this->storePublicFile($file, 'complaints/attachments', FileType::safeHashName($file));
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = FileType::safeUploadedMime($file);
        }

        $complaint = Complaint::create([
            'reference_number'        => null,
            'type'                     => 'complaint',
            'full_name'                => $validated['full_name'],
            'email'                    => $validated['email'],
            'phone'                    => $validated['phone'] ?? null,
            'subject'                  => $validated['subject'] ?? 'Public Complaint',
            'message'                  => $validated['message'],
            'attachment_path'          => $attachmentPath,
            'attachment_original_name' => $attachmentName,
            'attachment_mime'          => $attachmentMime,
            'status'                   => 'open',
            'payment_status'           => null,
        ]);

        $reference = 'COMP-' . now()->format('Ymd') . '-' . str_pad($complaint->id, 6, '0', STR_PAD_LEFT);
        $complaint->update(['reference_number' => $reference]);

        try {
            $contactEmail = 'info@zmc.org.zw';
            Mail::raw(
                "Dear {$complaint->full_name},\n\n"
                . "Your complaint has been received and registered by the Zimbabwe Media Commission (ZMC).\n"
                . "Reference number: {$reference}\n"
                . "Please keep this number for follow-up. ZMC will contact you in due course regarding your complaint.\n\n"
                . "Regards;\nComplaints Desk\nZimbabwe Media Commission",
                function ($message) use ($complaint, $reference) {
                    $message->to($complaint->email)
                        ->subject('ZMC complaint received — ' . $reference);
                }
            );
        } catch (\Throwable $mailException) {
            report($mailException);
        }

        return $this->corsJson([
            'status'    => 'success',
            'message'   => 'Complaint submitted successfully. Our team will contact you soon.',
            'reference' => $reference,
        ], 201);
    }
}
