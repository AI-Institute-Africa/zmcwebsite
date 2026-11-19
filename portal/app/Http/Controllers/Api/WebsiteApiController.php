<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccreditationRecord;
use App\Models\Application;
use App\Models\RegistrationRecord;
use App\Models\Complaint;
use App\Models\Vacancy;
use App\Models\News;
use App\Models\Notice;
use App\Models\Tender;
use App\Models\Event;
use App\Models\WebsiteDownload;
use App\Models\WebsiteMediaItem;
use App\Models\RegionalOffice;
use App\Models\WebsitePagePerson;
use App\Models\User;
use App\Notifications\WebsiteMessageReceived;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ZMC Website API Controller
 *
 * Provides public JSON endpoints consumed exclusively by the ZMC Website
 * configured in the environment. All responses include appropriate CORS headers
 * so the website can call these from the browser.
 *
 * Base path: /api/v1/
 *
 * Endpoints:
 *  GET  /api/v1/status                           - Health / API status
 *  GET  /api/v1/verify/accreditation/{number}    - Verify an accreditation card/number
 *  GET  /api/v1/verify/registration/{number}     - Verify a media-house registration number
 *  GET  /api/v1/accredited-practitioners         - Paginated list of active accredited practitioners
 *  GET  /api/v1/registered-mediahouses           - Paginated list of active registered media houses
 *  POST /api/v1/contact                          - Submit a "Contact Us" message from the website
 *  OPTIONS /api/v1/*                             - CORS preflight (handled globally below)
 */
class WebsiteApiController extends Controller
{
    // ─── CORS helpers ─────────────────────────────────────────────────────────

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

    private function corsJson($data, int $status = 200)
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin',  $this->corsOrigin())
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, X-Requested-With')
            ->header('Vary', 'Origin');
    }

    public function preflight()
    {
        return response('', 204)
            ->header('Access-Control-Allow-Origin',  $this->corsOrigin())
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, X-Requested-With')
            ->header('Vary', 'Origin');
    }

    // ─── 1. API Status ─────────────────────────────────────────────────────────

    /**
     * GET /api/v1/status
     * Simple liveness / readiness check for the website to confirm the API is up.
     */
    public function status()
    {
        return $this->corsJson([
            'status'    => 'ok',
            'service'   => 'ZMC Portal API',
            'version'   => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // ─── 2. Accreditation Verification ────────────────────────────────────────

    /**
     * GET /api/v1/verify/accreditation/{number}
     *
     * Allows the website to verify whether a given accreditation number (or
     * certificate number) is valid and currently active.
     *
     * @param  string  $number  The accreditation or certificate number to verify
     */
    public function verifyAccreditation(string $number)
    {
        $number = trim($number);

        if (empty($number)) {
            return $this->corsJson([
                'status'  => 'error',
                'message' => 'Accreditation number is required.',
            ], 422);
        }

        $record = AccreditationRecord::where(function ($q) use ($number) {
                $q->where('accreditation_number', $number)
                  ->orWhere('certificate_no', $number)
                  ->orWhere('record_number', $number);
            })
            ->where('status', 'active')
            ->first();

        if (!$record) {
            // Try finding any record (even expired) so we can give a better message
            $any = AccreditationRecord::where(function ($q) use ($number) {
                    $q->where('accreditation_number', $number)
                      ->orWhere('certificate_no', $number)
                      ->orWhere('record_number', $number);
                })
                ->first();

            if ($any) {
                return $this->corsJson([
                    'status'  => 'invalid',
                    'valid'   => false,
                    'message' => 'This accreditation number exists but is no longer active.',
                    'record_status' => $any->status,
                ], 200);
            }

            // Older issued cards may not have an AccreditationRecord row yet.
            // Resolve the saved card number from the application so its QR
            // link still opens the holder's public verification details.
            $application = Application::where('application_type', 'accreditation')
                ->where(function ($q) use ($number) {
                    $q->where('card_number', $number)
                        ->orWhere('certificate_number', $number)
                        ->orWhere('form_data->card_edits->accreditation_id', $number)
                        ->orWhere('form_data->accreditation_id', $number);
                })
                ->first();

            if ($application) {
                $form = (array) ($application->form_data ?? []);
                $edits = (array) ($form['card_edits'] ?? []);
                $fullName = trim(implode(' ', array_filter([
                    $form['first_name'] ?? null,
                    $form['other_names'] ?? null,
                    $form['surname'] ?? null,
                ]))) ?: ($application->applicant?->name ?? 'Media Practitioner');
                $issuedAt = $application->issued_at ?? $application->approved_at ?? $application->created_at;
                $expiresAt = $application->expiry_date ?? ($edits['valid_to'] ?? now()->endOfYear());
                $status = in_array($application->status, ['issued', 'card_generated'], true) ? 'active' : $application->status;
                $organisation = $form['employer_name'] ?? ($edits['organisation'] ?? '');
                if ($organisation === '' || $organisation === '—') {
                    $organisation = 'Freelance';
                }
                $issuedDate = $issuedAt ? \Carbon\Carbon::parse($issuedAt) : null;
                $expiryDate = $expiresAt ? \Carbon\Carbon::parse($expiresAt) : null;

                return $this->corsJson([
                    'status'  => 'success',
                    'valid'   => $status === 'active',
                    'message' => 'Accreditation record is valid and active.',
                    'data'    => [
                        'accreditation_number' => $edits['accreditation_id'] ?? $number,
                        'certificate_no'       => $edits['accreditation_id'] ?? $number,
                        'full_name'            => $fullName,
                        'organisation'         => $organisation,
                        'category'             => $application->accreditation_category_code ?? ($form['accreditation_category_code'] ?? '—'),
                        'designation'          => $form['designation'] ?? ($edits['designation'] ?? 'Media Practitioner'),
                        'status'               => $status,
                        'issued_at'            => $issuedDate?->toDateString(),
                        'expires_at'           => $expiryDate?->toDateString(),
                        'accreditation_year'   => $issuedDate?->year,
                    ],
                ]);
            }

            return $this->corsJson([
                'status'  => 'not_found',
                'valid'   => false,
                'message' => 'No accreditation record found for the provided number.',
            ], 200);
        }

        return $this->corsJson([
            'status'  => 'success',
            'valid'   => true,
            'message' => 'Accreditation record is valid and active.',
            'data'    => [
                'accreditation_number' => $record->accreditation_number,
                'certificate_no'       => $record->certificate_no,
                'full_name'            => $record->full_name,
                'organisation'         => $record->organisation,
                'category'             => $record->category,
                'designation'          => $record->designation,
                'medium'               => $record->medium,
                'nationality'          => $record->nationality,
                'status'               => $record->status,
                'issued_at'            => $record->issued_at?->toDateString(),
                'expires_at'           => $record->expires_at?->toDateString(),
                'accreditation_year'   => $record->accreditation_year,
            ],
        ]);
    }

    // ─── 3. Media House Registration Verification ─────────────────────────────

    /**
     * GET /api/v1/verify/registration/{number}
     *
     * Verify a media house registration number.
     *
     * @param  string  $number  The registration number to verify
     */
    public function verifyRegistration(string $number)
    {
        $number = trim($number);

        if (empty($number)) {
            return $this->corsJson([
                'status'  => 'error',
                'message' => 'Registration number is required.',
            ], 422);
        }

        $record = RegistrationRecord::where(function ($q) use ($number) {
                $q->where('registration_no', $number)
                  ->orWhere('record_number', $number);
            })
            ->where('status', 'active')
            ->first();

        if (!$record) {
            $any = RegistrationRecord::where(function ($q) use ($number) {
                    $q->where('registration_no', $number)
                      ->orWhere('record_number', $number);
                })
                ->first();

            if ($any) {
                return $this->corsJson([
                    'status'        => 'invalid',
                    'valid'         => false,
                    'message'       => 'This registration number exists but is no longer active.',
                    'record_status' => $any->status,
                ], 200);
            }

            return $this->corsJson([
                'status'  => 'not_found',
                'valid'   => false,
                'message' => 'No registration record found for the provided number.',
            ], 200);
        }

        return $this->corsJson([
            'status'  => 'success',
            'valid'   => true,
            'message' => 'Registration record is valid and active.',
            'data'    => [
                'registration_no'      => $record->registration_no,
                'organisation_name'    => $record->display_name,
                'category'             => $record->category,
                'physical_address'     => $record->physical_address,
                'town'                 => $record->town,
                'province'             => $record->province,
                'phone'                => $record->phone,
                'email'                => $record->email,
                'website'              => $record->website,
                'status'               => $record->status,
                'issued_at'            => $record->issued_at?->toDateString(),
                'expires_at'           => $record->expires_at?->toDateString(),
                'registration_year'    => $record->registration_year,
            ],
        ]);
    }

    // ─── 4. Public Accredited Practitioners List ───────────────────────────────

    /**
     * GET /api/v1/accredited-practitioners
     *
     * Returns a paginated, publicly-safe list of active accredited media practitioners.
     * Intended to power a public directory on the ZMC website.
     *
     * Query params:
     *  - page        (int)    Page number (default 1)
     *  - per_page    (int)    Results per page (default 20, max 100)
     *  - search      (string) Filter by name or organisation
     *  - category    (string) Filter by accreditation category
     *  - medium      (string) Filter by medium (print, broadcast, online, etc.)
     *  - year        (int)    Filter by accreditation year
     */
    public function accreditedPractitioners(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $search  = trim((string) $request->input('search', ''));
        $year    = $request->input('year');
        $category = $request->input('category');
        $medium  = $request->input('medium');

        $query = AccreditationRecord::where('status', 'active')
            ->orderBy('surname')
            ->orderBy('first_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhere('other_names', 'like', "%{$search}%")
                  ->orWhere('organisation', 'like', "%{$search}%")
                  ->orWhere('accreditation_number', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($medium) {
            $query->where('medium', $medium);
        }

        if ($year) {
            $query->where('accreditation_year', (int) $year);
        }

        $records = $query->paginate($perPage);

        return $this->corsJson([
            'status' => 'success',
            'data'   => $records->map(fn ($r) => [
                'accreditation_number' => $r->accreditation_number,
                'full_name'            => $r->full_name,
                'organisation'         => $r->organisation,
                'category'             => $r->category,
                'designation'          => $r->designation,
                'medium'               => $r->medium,
                'nationality'          => $r->nationality,
                'accreditation_year'   => $r->accreditation_year,
                'issued_at'            => $r->issued_at?->toDateString(),
                'expires_at'           => $r->expires_at?->toDateString(),
            ])->values(),
            'meta' => [
                'current_page'  => $records->currentPage(),
                'last_page'     => $records->lastPage(),
                'per_page'      => $records->perPage(),
                'total'         => $records->total(),
                'from'          => $records->firstItem(),
                'to'            => $records->lastItem(),
            ],
        ]);
    }

    // ─── 5. Public Registered Media Houses List ────────────────────────────────

    /**
     * GET /api/v1/registered-mediahouses
     *
     * Returns a paginated list of active registered media houses / organisations.
     *
     * Query params:
     *  - page        (int)    Page number (default 1)
     *  - per_page    (int)    Results per page (default 20, max 100)
     *  - search      (string) Filter by name or address
     *  - category    (string) Filter by registration category
     *  - province    (string) Filter by province
     *  - town        (string) Filter by town
     */
    public function registeredMediahouses(Request $request)
    {
        $perPage  = min((int) $request->input('per_page', 20), 100);
        $search   = trim((string) $request->input('search', ''));
        $category = $request->input('category');
        $province = $request->input('province');
        $town     = $request->input('town');

        $query = RegistrationRecord::where('status', 'active')
            ->orderBy('organisation_name')
            ->orderBy('entity_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('organisation_name', 'like', "%{$search}%")
                  ->orWhere('entity_name', 'like', "%{$search}%")
                  ->orWhere('registration_no', 'like', "%{$search}%")
                  ->orWhere('town', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($province) {
            $query->where('province', 'like', "%{$province}%");
        }

        if ($town) {
            $query->where('town', 'like', "%{$town}%");
        }

        $records = $query->paginate($perPage);

        return $this->corsJson([
            'status' => 'success',
            'data'   => $records->map(fn ($r) => [
                'registration_no'   => $r->registration_no,
                'organisation_name' => $r->display_name,
                'category'          => $r->category,
                'physical_address'  => $r->physical_address,
                'town'              => $r->town,
                'province'          => $r->province,
                'phone'             => $r->phone,
                'email'             => $r->email,
                'website'           => $r->website,
                'issued_at'         => $r->issued_at?->toDateString(),
                'expires_at'        => $r->expires_at?->toDateString(),
            ])->values(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page'    => $records->lastPage(),
                'per_page'     => $records->perPage(),
                'total'        => $records->total(),
                'from'         => $records->firstItem(),
                'to'           => $records->lastItem(),
            ],
        ]);
    }

    // ─── 6. Contact Us ────────────────────────────────────────────────────────

    /**
     * POST /api/v1/contact
     *
     * Accepts a "Contact Us" form submission from the ZMC website and stores it
     * as a complaint/enquiry record for staff to action.
     *
     * Body (multipart/form-data or application/json):
     *  - full_name   (required) string
     *  - email       (required) email
     *  - phone       (optional) string
     *  - subject     (optional) string
     *  - message     (required) string, min 10 chars
     *  - attachment  (optional) file — PDF/Word/image, max 10 MB
     */
    public function contact(Request $request)
    {
        $validated = $request->validate([
            'full_name'  => 'required|string|min:2|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'subject'    => 'nullable|string|max:255',
            'message'    => 'required|string|min:10|max:5000',
            'attachment' => 'nullable|file|extensions:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;

        if ($request->hasFile('attachment')) {
            $file           = $request->file('attachment');
            $attachmentPath = $this->storePublicFile($file, 'contact/attachments', FileType::safeHashName($file));
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = FileType::safeUploadedMime($file);
        }

        $enquiry = Complaint::create([
            'type'                        => 'enquiry',
            'full_name'                   => $validated['full_name'],
            'email'                       => $validated['email'],
            'phone'                       => $validated['phone'] ?? null,
            'subject'                     => $validated['subject'] ?? 'Website Enquiry',
            'message'                     => $validated['message'],
            'attachment_path'             => $attachmentPath,
            'attachment_original_name'    => $attachmentName,
            'attachment_mime'             => $attachmentMime,
            'status'                      => 'open',
            'priority'                    => 'normal',
            'response_due_at'             => now()->addWeekdays(2),
            'payment_status'              => null,
        ]);

        $reference = 'ENQ-' . str_pad($enquiry->id, 6, '0', STR_PAD_LEFT);
        $enquiry->update(['reference_number' => $reference]);

        // Keep the PR queue visible inside the portal. A notification failure
        // must never make a successfully stored website message fail.
        try {
            User::role('pr_officer')->get()->each->notify(new WebsiteMessageReceived($enquiry));
        } catch (\Throwable $e) {
            Log::warning('Website message staff notification failed', ['enquiry_id' => $enquiry->id, 'error' => $e->getMessage()]);
        }

        // Send the sender a confirmation containing the reference number. Mail
        // delivery is best effort; the message is already safely stored.
        try {
            Mail::raw("Thank you for contacting the Zimbabwe Media Commission.\n\nYour reference number is {$reference}. Our team will respond within 2–3 business days.\n\nPlease keep this reference for follow-up.", function ($mail) use ($validated, $reference) {
                $mail->to($validated['email'])->subject('ZMC contact enquiry received: ' . $reference);
                $mail->replyTo(config('organization.public_email'), 'Zimbabwe Media Commission');
            });
        } catch (\Throwable $e) {
            Log::warning('Website message confirmation email failed', ['enquiry_id' => $enquiry->id, 'error' => $e->getMessage()]);
        }

        return $this->corsJson([
            'status'    => 'success',
            'message'   => 'Thank you for contacting ZMC. Our team will respond within 2–3 business days.',
            'reference' => $reference,
        ], 201);
    }
    // ─── PR Content Endpoints ────────────────────────────────────────────────

    public function vacancies(Request $request)
    {
        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:100'],
        ]);
        $limit = $filters['limit'] ?? 15;
        $vacancies = Vacancy::where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('closing_at')->orWhere('closing_at', '>=', now()->startOfDay());
            })
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")))
            ->when($filters['department'] ?? null, fn ($query, $department) => $query->where('department', $department))
            ->latest('published_at')
            ->paginate($limit);

        $vacancies->setCollection($vacancies->getCollection()->map(fn (Vacancy $vacancy) => $this->formatVacancy($vacancy)));

        return $this->corsJson($vacancies);
    }

    public function vacancy(string $id)
    {
        $vacancy = Vacancy::where('is_published', true)
            ->where(fn ($query) => $query->whereNull('closing_at')->orWhere('closing_at', '>=', now()->startOfDay()))
            ->findOrFail($id);
        return $this->corsJson($this->formatVacancy($vacancy));
    }

    public function news(Request $request)
    {
        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);
        $limit = $filters['limit'] ?? 15;
        $news = News::where('is_published', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->latest('published_at')
            ->paginate($limit);

        $news->setCollection($news->getCollection()->map(fn (News $item) => $this->formatNews($item)));

        return $this->corsJson($news);
    }

    public function newsItem(string $id)
    {
        $news = News::where('is_published', true)->findOrFail($id);
        return $this->corsJson($this->formatNews($news));
    }

    private function formatNews(News $news): array
    {
        $data = $news->toArray();
        $storageUrl = asset('storage');
        $paths = array_values(array_unique(array_filter(array_merge((array) ($data['image_paths'] ?? []), [$data['image_path'] ?? null]))));
        $data['image_paths'] = $paths;
        $data['image_urls'] = array_map(fn ($path) => $storageUrl . '/' . $path, $paths);
        $data['image_url'] = $data['image_urls'][0] ?? null;
        if (strcasecmp((string) ($data['category'] ?? ''), 'Statement') === 0) {
            $data['category'] = 'Press Release';
        }

        return $data;
    }

    private function formatVacancy(Vacancy $vacancy): array
    {
        $data = $vacancy->toArray();
        foreach ([
            'salary_range', 'salary_display', 'reports_to', 'supervises',
            'application_requirements', 'interview_dates', 'expected_start_date',
            'shortlisting_criteria', 'submission_method', 'submission_address',
        ] as $privateOrRemovedField) {
            unset($data[$privateOrRemovedField]);
        }

        $closingDate = $data['closing_at'] ?? null;
        if (!$closingDate) {
            $data['vacancy_status'] = 'Open';
        } else {
            $closingDate = \Illuminate\Support\Carbon::parse($closingDate)->startOfDay();
            $data['vacancy_status'] = $closingDate->lt(now()->startOfDay())
                ? 'Closed'
                : ($closingDate->lte(now()->addDays(7)->startOfDay()) ? 'Closing Soon' : 'Open');
        }

        return $data;
    }

    public function notices(Request $request)
    {
        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);
        $limit = $filters['limit'] ?? 15;
        $notices = Notice::where('is_published', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString()))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('notice_category', $category))
            ->latest('published_at')
            ->paginate($limit);

        return $this->corsJson($notices);
    }

    public function tenders(Request $request)
    {
        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
        $limit = $filters['limit'] ?? 15;
        $tenders = Tender::where('is_published', true)
            ->where(fn ($query) => $query->whereNull('closing_at')->orWhere('closing_at', '>=', now()->startOfDay()))
            ->where(fn ($query) => $query->whereNull('tender_status')->orWhereNotIn('tender_status', ['Closed', 'Cancelled', 'Awarded']))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('tender_reference', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%")->orWhere('issuing_organisation', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('tender_status', $status))
            ->latest('published_at')
            ->paginate($limit);

        $tenders->setCollection($tenders->getCollection()->map(function (Tender $tender) {
            $item = $tender->toArray();
            $item['egp_url'] = $tender->bidding_documents_link;
            return $item;
        }));

        return $this->corsJson($tenders);
    }

    public function tender(string $id)
    {
        $tender = Tender::where('is_published', true)
            ->where(fn ($query) => $query->whereNull('closing_at')->orWhere('closing_at', '>=', now()->startOfDay()))
            ->where(fn ($query) => $query->whereNull('tender_status')->orWhereNotIn('tender_status', ['Closed', 'Cancelled', 'Awarded']))
            ->findOrFail($id);
        $data = $tender->toArray();
        $data['egp_url'] = $tender->bidding_documents_link;

        return $this->corsJson($data);
    }

    public function events(Request $request)
    {
        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $limit = $filters['limit'] ?? 15;
        $events = Event::where('is_published', true)
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->orderBy('starts_at', 'desc')
            ->paginate($limit);

        return $this->corsJson($events);
    }

    public function downloads(Request $request)
    {
        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $limit = $filters['limit'] ?? 15;
        $downloads = WebsiteDownload::where('is_published', true)
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->latest('published_at')
            ->paginate($limit);

        return $this->corsJson($downloads);
    }

    public function media(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:image,video'],
            'category' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $items = WebsiteMediaItem::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($data['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($data['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($data['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->orderBy('display_order')->latest('published_at')
            ->paginate($data['limit'] ?? 15);

        $items->setCollection($items->getCollection()->map(fn (WebsiteMediaItem $item) => $this->formatPublicMedia($item)));
        return $this->corsJson($items);
    }

    public function mediaItem(string $id)
    {
        $item = WebsiteMediaItem::where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->findOrFail($id);

        return $this->corsJson($this->formatPublicMedia($item));
    }

    public function offices(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $offices = RegionalOffice::query()
            ->where('is_published', true)
            ->when($data['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('region', 'like', "%{$search}%")))
            ->orderBy('display_order')->orderBy('name')
            ->paginate($data['limit'] ?? 50)
            ->through(fn (RegionalOffice $office) => $office->only(['id', 'code', 'name', 'region', 'address', 'phone', 'email', 'schedule']));

        return $this->corsJson($offices);
    }

    public function services()
    {
        $hubUrl = \App\Support\MasterSettings::get('website_content.media_hub_url', config('services.media_hub.url'));

        return $this->corsJson([
            'status' => 'success',
            'data' => [
                'media_training_hub' => [
                    'url' => $hubUrl,
                    'available' => is_string($hubUrl) && filter_var($hubUrl, FILTER_VALIDATE_URL) !== false,
                ],
            ],
        ]);
    }

    public function peoplePage(string $page)
    {
        abort_unless(in_array($page, ['commissioners', 'secretariat'], true), 404);

        $people = WebsitePagePerson::query()
            ->where('page_key', $page)
            ->where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn (WebsitePagePerson $person) => [
                'id' => $person->id,
                'name' => $person->name,
                'position' => $person->position,
                'department' => $person->department,
                'biography' => $person->biography,
                'image_url' => \Illuminate\Support\Facades\Storage::disk('public')->url($person->image_path),
                'display_order' => $person->sort_order,
            ]);

        return $this->corsJson([
            'status' => 'success',
            'page' => $page,
            'updated_at' => $people->isEmpty() ? null : WebsitePagePerson::where('page_key', $page)->where('is_published', true)->max('updated_at'),
            'data' => $people,
        ]);
    }

    private function formatPublicMedia(WebsiteMediaItem $item): array
    {
        $data = $item->only(['id', 'type', 'title', 'description', 'category', 'external_url', 'published_at', 'display_order']);
        $data['file_url'] = $item->file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($item->file_path) : null;
        $data['thumbnail_url'] = $item->thumbnail_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($item->thumbnail_path) : null;
        return $data;
    }
}
