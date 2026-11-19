<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AccreditationRecord;
use App\Models\Application;
use App\Models\CardTemplate;
use App\Models\DesignStudioTemplate;
use App\Models\GeneratedDocument;
use App\Models\PrintLog;
use App\Models\RegistrationRecord;
use App\Services\ActivityLogger;
use App\Services\ApplicationWorkflow;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductionController extends Controller
{
    /**
     * Build a production query with region scoping (collection_region).
     */
    private function baseQuery(array $statuses)
    {
        $user = Auth::user();
        $q = Application::query()
            ->with(['applicant'])
            ->withCount('documents')
            ->whereIn('status', $statuses)
            ->latest();

        // Concurrency visibility logic
        $q->where(function ($qq) use ($user) {
            $qq->whereNull('assigned_officer_id')
                ->orWhere('assigned_officer_id', $user->id);
        });

        $q->where(function ($qq) use ($user) {
            $qq->whereNull('locked_at')
                ->orWhere('locked_at', '<=', now()->subHours(2))
                ->orWhere('locked_by', $user->id);
        });

        // Region scoping: production handlers (whose ONLY scoped role is
        // production/accreditation_officer) see only applications whose
        // collection_region matches one of their assigned regions. Super
        // admins and unscoped roles (registrar, accounts, director, etc.)
        // bypass the filter entirely. Users with the scoped role but NO
        // assigned regions see nothing (operationally safer).
        if (\App\Support\RegionAccessGuard::userIsScoped($user)) {
            $regions = $user->assignedCollectionRegions();
            if (empty($regions)) {
                $q->whereRaw('1=0'); // no regions assigned => empty result
            } else {
                $q->whereIn('collection_region', $regions);
            }
        }

        return $q;
    }

    public function dashboard(Request $request)
    {
        $applications = $this->baseQuery([
            Application::PAYMENT_VERIFIED,
            Application::PRODUCTION_QUEUE,
            Application::PRODUCTION_APPROVED,
            Application::CARD_GENERATED,
            Application::CERT_GENERATED,
            Application::PRINTED,
        ])->paginate(20)->withQueryString();

        // KPI counters (region scoped)
        $kpiQueue = (clone $this->baseQuery([Application::PAYMENT_VERIFIED, Application::PRODUCTION_QUEUE, Application::PRODUCTION_APPROVED]))->count();
        $kpiToPrint = (clone $this->baseQuery([Application::CARD_GENERATED, Application::CERT_GENERATED]))->count();
        $kpiPrinted = (clone $this->baseQuery([Application::PRINTED]))->count();
        $kpiIssued = (clone $this->baseQuery([Application::ISSUED]))->count();

        // "Today" counts (created_at as proxy for production events)
        $today = now()->startOfDay();
        $kpiGeneratedToday = (clone $this->baseQuery([Application::CARD_GENERATED, Application::CERT_GENERATED]))
            ->where('updated_at', '>=', $today)->count();
        $kpiIssuedToday = (clone $this->baseQuery([Application::ISSUED]))
            ->where('updated_at', '>=', $today)->count();

        return view('staff.production.dashboard', compact(
            'applications',
            'kpiQueue',
            'kpiToPrint',
            'kpiPrinted',
            'kpiIssued',
            'kpiGeneratedToday',
            'kpiIssuedToday'
        ));
    }

    /**
     * Production Queue (Registrar approved → Production).
     */
    public function queue()
    {
        $applications = $this->baseQuery([Application::PAYMENT_VERIFIED, Application::PRODUCTION_QUEUE, Application::PRODUCTION_APPROVED])->paginate(20);
        return view('staff.production.list', [
            'pageTitle' => 'Production Queue',
            'pageNote' => 'Items approved by the Registrar and awaiting production actions.',
            'applications' => $applications,
            'mode' => 'queue',
        ]);
    }

    /**
     * Card production workspace (Accreditation only).
     */
    public function cards()
    {
        $applications = $this->baseQuery([
            Application::PRODUCTION_QUEUE,
            Application::PRODUCTION_APPROVED,
            Application::CARD_GENERATED,
            Application::PRINTED,
            Application::ISSUED,
        ])
            ->where('application_type', 'accreditation')
            ->paginate(20);

        return view('staff.production.list', [
            'pageTitle' => 'Card Production',
            'pageNote' => 'Generate / print accreditation cards (AP3/AP5).',
            'applications' => $applications,
            'mode' => 'cards',
        ]);
    }

    /**
     * Certificate production workspace (Registration only).
     */
    public function certificates()
    {
        $applications = $this->baseQuery([
            Application::PRODUCTION_QUEUE,
            Application::PRODUCTION_APPROVED,
            Application::CERT_GENERATED,
            Application::PRINTED,
            Application::ISSUED,
        ])
            ->where('application_type', 'registration')
            ->paginate(20);

        return view('staff.production.list', [
            'pageTitle' => 'Certificate Production',
            'pageNote' => 'Generate / print registration certificates (AP1).',
            'applications' => $applications,
            'mode' => 'certificates',
        ]);
    }

    /**
     * Printing queue (generated but not yet printed).
     */
    public function printing(Request $request)
    {
        $query = $this->baseQuery([
            Application::CARD_GENERATED,
            Application::CERT_GENERATED,
        ]);
        if ($request->filled('collection_region')) $query->where('collection_region', (string) $request->input('collection_region'));
        if ($request->filled('application_type') && in_array($request->input('application_type'), ['accreditation', 'registration'], true)) {
            $query->where('application_type', $request->input('application_type'));
        }
        if ($request->filled('category')) {
            $category = (string) $request->input('category');
            $query->where(function ($q) use ($category) {
                $q->where('accreditation_category_code', $category)
                  ->orWhere('media_house_category_code', $category);
            });
        }
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->input('date_to'));
        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('reference', 'like', '%' . $term . '%')
                  ->orWhereHas('applicant', fn($applicant) => $applicant->where('name', 'like', '%' . $term . '%'));
            });
        }
        $applications = $query->paginate(20)->withQueryString();

        return view('staff.production.list', [
            'pageTitle' => 'Printing Queue',
            'pageNote' => 'Items generated and ready to be marked as printed (single or batch).',
            'applications' => $applications,
            'mode' => 'printing',
        ]);
    }

    /**
     * Issuance queue (printed but not yet issued).
     */
    public function issuance()
    {
        $applications = $this->baseQuery([Application::PRINTED])->paginate(20);

        return view('staff.production.list', [
            'pageTitle' => 'Issuance & Collection',
            'pageNote' => 'Printed items ready for collection/dispatch. Mark items as issued upon handover.',
            'applications' => $applications,
            'mode' => 'issuance',
        ]);
    }

    /**
     * Issued register (logbook).
     */
    public function issuedRegister()
    {
        $applications = $this->baseQuery([Application::ISSUED])->paginate(20);

        return view('staff.production.list', [
            'pageTitle' => 'Issued Register',
            'pageNote' => 'Official register of issued cards/certificates (region scoped).',
            'applications' => $applications,
            'mode' => 'register',
        ]);
    }

    /**
     * Reports (placeholder page).
     */
    public function reports()
    {
        $kpis = [
            'in_queue' => (clone $this->baseQuery([Application::PRODUCTION_QUEUE]))->count(),
            'to_print' => (clone $this->baseQuery([Application::CARD_GENERATED, Application::CERT_GENERATED]))->count(),
            'printed' => (clone $this->baseQuery([Application::PRINTED]))->count(),
            'issued' => (clone $this->baseQuery([Application::ISSUED]))->count(),
        ];

        return view('staff.production.reports', compact('kpis'));
    }

    /**
     * Region authorization guard. Production users may only act on applications
     * whose collection_region matches one of their assigned regions. Super admin
     * bypasses. A production user with no assigned regions cannot access any
     * application.
     */
    private function ensureCanAccessRegion(Application $application): void
    {
        \App\Support\RegionAccessGuard::ensure($application);
    }

    public function approveForGeneration(Application $application, Request $request)
    {
        $this->ensureCanAccessRegion($application);

        if ($application->status !== Application::PRODUCTION_QUEUE) {
            return redirect()->back()->with('error', 'Application is not in the Production Queue.');
        }

        ApplicationWorkflow::transition($application, Application::PRODUCTION_APPROVED, [
            'notes' => $request->input('notes', 'Application approved for production generation.'),
        ]);

        ActivityLogger::log('production.approved', $application, [
            'message' => 'Approved application for card/certificate generation.',
        ]);

        return redirect()->route('staff.production.show', $application)->with('success', 'Application approved for generation.');
    }

    public function show(Application $application)
    {
        $this->ensureCanAccessRegion($application);
        // Try to claim
        if (!$application->claim(auth()->user())) {
            $lockerName = $application->lockedBy ? $application->lockedBy->name : 'another officer';
            return redirect()->back()->with('error', "This application is currently being worked on by {$lockerName}.");
        }

        if (in_array($application->application_type, ['registration', 'renewal'], true)) {
            $expectedFor = 'registration';
        } else {
            $expectedFor = ($application->journalist_scope === 'foreign') ? 'accreditation_foreign' : 'accreditation_local';
        }

        $approvedCardTemplates = DesignStudioTemplate::approved()
            ->cards()
            ->where(function ($query) use ($expectedFor) {
                $query->where('template_for', $expectedFor)
                    ->orWhereNull('template_for');
            })
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at')
            ->get();

        $approvedCertificateTemplates = DesignStudioTemplate::approved()
            ->certificates()
            ->where(function ($query) use ($expectedFor) {
                $query->where('template_for', $expectedFor)
                    ->orWhereNull('template_for');
            })
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at')
            ->get();

        $application->load(['applicant', 'documents', 'messages', 'workflowLogs', 'lockedBy', 'paymentSubmissions', 'payments', 'comments.user']);

        return view('staff.production.show', compact(
            'application',
            'approvedCardTemplates',
            'approvedCertificateTemplates'
        ));
    }

    public function unlock(Application $application)
    {
        $this->ensureCanAccessRegion($application);
        if ($application->locked_by === auth()->id()) {
            $application->unlock();
            return redirect()->route('staff.production.dashboard')->with('success', 'Application released.');
        }
        return back();
    }

    public function generateCard(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);

        $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:design_studio_templates,id'],
        ]);

        $template = $this->resolveDesignStudioTemplate($request, $application, DesignStudioTemplate::TYPE_CARD);
        if (!$template) {
            return back()->with('error', 'No approved accreditation card template is available for this application.');
        }

        $from = $application->status;
        $this->ensureRecordCreated($application);

        $generatedDocument = $this->createOrUpdateGeneratedDocument(
            $application,
            $template,
            'accreditation_card'
        );

        $application->update([
            'status' => Application::CARD_GENERATED,
            'printed_by' => auth()->id(),
            'printed_at' => now(),
        ]);

        $this->recordPrintLog($application, $generatedDocument, 'accreditation');
        $application->refresh();
        $this->audit('production_generate_card', $application, $from, $application->status, [
            'template_id' => $template->id,
            'generated_document_id' => $generatedDocument->id,
        ]);

        return back()->with('success', 'Card generated and record created.');
    }

    public function cardPreview(Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $application->load(['applicant', 'documents']);

        // Pull any previous edits
        $edits = (array) ($application->form_data['card_edits'] ?? []);

        // The active Design Studio card is the production source of truth for
        // this preview. Keep the legacy preview as a fallback for applications
        // that do not yet have an active design.
        $expectedFor = strtoupper((string) ($application->journalist_scope ?? 'local')) === 'FOREIGN'
            ? 'accreditation_foreign'
            : 'accreditation_local';
        $designTemplate = DesignStudioTemplate::approved()
            ->cards()
            ->where('template_for', $expectedFor)
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at')
            ->first();

        return view('staff.production.card_preview', compact('application', 'edits', 'designTemplate'));
    }

    public function cardPrint(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'card_payload' => ['required', 'array'],
            'template' => ['nullable', 'string'],
        ]);

        // Persist edits into form_data
        $form = (array) ($application->form_data ?? []);
        $form['card_edits'] = $data['card_payload'];
        $form['card_template'] = $data['template'] ?? 'default';
        $application->form_data = $form;
        $application->save();

        // Mark generated (workflow state) when user prints
        $from = $application->status;
        if ($application->status !== Application::CARD_GENERATED) {
            ApplicationWorkflow::transition($application, Application::CARD_GENERATED, 'production_generate_card');
            $application->refresh();
            $this->audit('production_generate_card', $application, $from, $application->status);
        }

        $application->load(['applicant', 'documents', 'accreditationRecord']);
        $payload = $data['card_payload'];
        // A practitioner card is valid from the generation date through the
        // end of that calendar year. Enforce this server-side so stale form
        // edits cannot print an incorrect validity period.
        $cardIssueDate = now();
        $payload['valid_from'] = $cardIssueDate->format('Y-m-d');
        $payload['valid_to'] = $cardIssueDate->copy()->endOfYear()->format('Y-m-d');
        $payloadScope = strtoupper((string) ($payload['scope'] ?? data_get($application->form_data, 'journalist_scope', 'LOCAL')));
        $payload['id_number'] = $payloadScope === 'FOREIGN'
            ? data_get($application->form_data, 'passport_no', data_get($application->form_data, 'passport_number', data_get($application->form_data, 'id_number', '—')))
            : data_get($application->form_data, 'national_reg_no', data_get($application->form_data, 'national_id', data_get($application->form_data, 'id_number', '—')));
        $verificationNumber = $payload['accreditation_id']
            ?? $application->accreditationRecord->accreditation_number
            ?? $application->accreditationRecord->certificate_no
            ?? $application->accreditationRecord->record_number;
        $payload['qr_value'] = $verificationNumber
            ? 'https://zmc.org.zw/verify-accreditation?number=' . rawurlencode((string) $verificationNumber)
            : route('public.verify', 'invalid');
        $payload['reg_no'] = $application->accreditationRecord->certificate_no ?? ($payload['ref'] ?? '—');

        // Add passport photo URL to payload
        $passportPhoto = $application->documents->where('doc_type', 'passport_photo')->first();
        if ($passportPhoto) {
            $payload['photo_url'] = $passportPhoto->url;
        }

        $template = $data['template'] ?? 'default';
        $template_data = $this->getCardTemplateData($template);

        $isForeign = strtoupper((string) ($payload['scope'] ?? 'LOCAL')) === 'FOREIGN';

        $view = $this->getCardView($template);

        return view($view, compact('application', 'payload', 'template', 'template_data', 'isForeign'));
    }


    public function cardPrintBack(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'card_payload' => ['required', 'array'],
            'template' => ['nullable', 'string'],
        ]);

        // Persist edits into form_data (same payload)
        $form = (array) ($application->form_data ?? []);
        $form['card_edits'] = $data['card_payload'];
        $form['card_template'] = $data['template'] ?? 'default';
        $application->form_data = $form;
        $application->save();

        // Mark generated (workflow state) when user prints
        $from = $application->status;
        if ($application->status !== Application::CARD_GENERATED) {
            ApplicationWorkflow::transition($application, Application::CARD_GENERATED, 'production_generate_card');
            $application->refresh();
            $this->audit('production_generate_card', $application, $from, $application->status);
        }

        $application->load(['applicant', 'accreditationRecord']);
        $payload = $data['card_payload'];
        $cardIssueDate = now();
        $payload['valid_from'] = $cardIssueDate->format('Y-m-d');
        $payload['valid_to'] = $cardIssueDate->copy()->endOfYear()->format('Y-m-d');
        $verificationNumber = $payload['accreditation_id']
            ?? $application->accreditationRecord->accreditation_number
            ?? $application->accreditationRecord->certificate_no
            ?? $application->accreditationRecord->record_number;
        $payload['qr_value'] = $verificationNumber
            ? 'https://zmc.org.zw/verify-accreditation?number=' . rawurlencode((string) $verificationNumber)
            : route('public.verify', 'invalid');
        $payload['reg_no'] = $application->accreditationRecord->certificate_no ?? ($payload['ref'] ?? '—');

        $template = $data['template'] ?? 'default';
        $template_data = $this->getCardTemplateData($template);

        $view = $this->getCardBackView($template);

        return view($view, compact('application', 'payload', 'template', 'template_data'));
    }

    /**
     * Open a single print job containing both sides of the accreditation card.
     * The browser's duplex setting can then print page 1 and page 2 together.
     */
    public function cardPrintDuplex(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'card_payload' => ['required', 'array'],
            'template' => ['nullable', 'string'],
        ]);

        $form = (array) ($application->form_data ?? []);
        $form['card_edits'] = $data['card_payload'];
        $form['card_template'] = $data['template'] ?? 'default';
        $application->form_data = $form;
        $application->save();

        if (!$application->accreditationRecord) {
            $this->ensureRecordCreated($application);
        }
        $application->load(['applicant', 'documents', 'accreditationRecord']);

        $payload = $data['card_payload'];
        $cardIssueDate = now();
        $payload['valid_from'] = $cardIssueDate->format('Y-m-d');
        $payload['valid_to'] = $cardIssueDate->copy()->endOfYear()->format('Y-m-d');
        $verificationNumber = $payload['accreditation_id']
            ?? $application->accreditationRecord->accreditation_number
            ?? $application->accreditationRecord->certificate_no
            ?? $application->accreditationRecord->record_number;
        $payload['qr_value'] = $verificationNumber
            ? 'https://zmc.org.zw/verify-accreditation?number=' . rawurlencode((string) $verificationNumber)
            : route('public.verify', 'invalid');
        $payload['reg_no'] = $application->accreditationRecord->certificate_no ?? ($payload['accreditation_id'] ?? $payload['ref'] ?? '—');
        $payload['full_name'] = $payload['full_name'] ?? $payload['name'] ?? $application->applicant?->name ?? '—';
        $payloadScope = strtoupper((string) ($payload['scope'] ?? data_get($application->form_data, 'journalist_scope', 'LOCAL')));
        $submittedId = $payloadScope === 'FOREIGN'
            ? data_get($application->form_data, 'passport_no', data_get($application->form_data, 'passport_number', data_get($application->form_data, 'id_number', '—')))
            : data_get($application->form_data, 'national_reg_no', data_get($application->form_data, 'national_id', data_get($application->form_data, 'id_number', '—')));
        // The ID printed on the card must come from the submitted application.
        $payload['id_number'] = $submittedId ?: ($payload['id_number'] ?? '—');
        $payload['media_org'] = $payload['media_org'] ?? $payload['organisation'] ?? data_get($application->form_data, 'employer_name', 'Freelance');
        $payload['designation'] = $payload['designation'] ?? data_get($application->form_data, 'designation', 'Media Practitioner');
        $payload['year'] = $payload['year'] ?? now()->year;

        $passportPhoto = $application->documents->where('doc_type', 'passport_photo')->first();
        if ($passportPhoto) {
            $payload['photo_url'] = $passportPhoto->url;
        }

        $template = $data['template'] ?? 'default';
        $template_data = $this->getCardTemplateData($template);
        $isForeign = strtoupper((string) ($payload['scope'] ?? 'LOCAL')) === 'FOREIGN';
        // Both scopes now use the supplied 2027 front artwork; the template selects the correct artwork.
        $view = 'staff.production.card_print_2027_duplex';

        return view($view, compact('application', 'payload', 'template', 'template_data', 'isForeign'));
    }

    private function getCardTemplateData(string $template): array
    {
        $variations = [
            'default' => [
                'primary_color' => '#1a237e',
                'secondary_color' => '#f5c518',
                'bg_style' => 'gradient',
                'layout' => 'standard'
            ],
            'modern_dark' => [
                'primary_color' => '#212121',
                'secondary_color' => '#fbbf24',
                'bg_style' => 'solid',
                'layout' => 'standard'
            ],
            'eco_green' => [
                'primary_color' => '#1a1a1a',
                'secondary_color' => '#fde68a',
                'bg_style' => 'gradient',
                'layout' => 'standard'
            ],
            'royal_gold' => [
                'primary_color' => '#4a148c',
                'secondary_color' => '#ffd54f',
                'bg_style' => 'pattern',
                'layout' => 'standard'
            ],
            'ocean_blue' => [
                'primary_color' => '#01579b',
                'secondary_color' => '#4fc3f7',
                'bg_style' => 'gradient',
                'layout' => 'standard'
            ],
            'crimson_pro' => [
                'primary_color' => '#b71c1c',
                'secondary_color' => '#ef9a9a',
                'bg_style' => 'solid',
                'layout' => 'standard'
            ],
            'executive_navy' => [
                'primary_color' => '#1a237e',
                'secondary_color' => '#f5c518',
                'bg_style' => 'gradient',
                'layout' => 'executive'
            ],
            'executive_charcoal' => [
                'primary_color' => '#212121',
                'secondary_color' => '#fbbf24',
                'bg_style' => 'solid',
                'layout' => 'executive'
            ],
            'executive_emerald' => [
                'primary_color' => '#064e3b',
                'secondary_color' => '#6ee7b7',
                'bg_style' => 'gradient',
                'layout' => 'executive'
            ],
            'diplomat_navy' => [
                'primary_color' => '#1a237e',
                'secondary_color' => '#f5c518',
                'bg_style' => 'gradient',
                'layout' => 'diplomat'
            ],
            'diplomat_burgundy' => [
                'primary_color' => '#7f1d1d',
                'secondary_color' => '#fbbf24',
                'bg_style' => 'solid',
                'layout' => 'diplomat'
            ],
            'diplomat_forest' => [
                'primary_color' => '#14532d',
                'secondary_color' => '#d4a574',
                'bg_style' => 'gradient',
                'layout' => 'diplomat'
            ],
            'press_navy' => [
                'primary_color' => '#1a237e',
                'secondary_color' => '#f5c518',
                'bg_style' => 'gradient',
                'layout' => 'press'
            ],
            'press_crimson' => [
                'primary_color' => '#b71c1c',
                'secondary_color' => '#fbbf24',
                'bg_style' => 'solid',
                'layout' => 'press'
            ],
            'press_teal' => [
                'primary_color' => '#004d40',
                'secondary_color' => '#80cbc4',
                'bg_style' => 'gradient',
                'layout' => 'press'
            ],
            'heritage_gold' => [
                'primary_color' => '#1a237e',
                'secondary_color' => '#d4a574',
                'bg_style' => 'gradient',
                'layout' => 'heritage'
            ],
            'heritage_green' => [
                'primary_color' => '#1a5f2a',
                'secondary_color' => '#f5c518',
                'bg_style' => 'gradient',
                'layout' => 'heritage'
            ],
            'heritage_bronze' => [
                'primary_color' => '#78350f',
                'secondary_color' => '#fbbf24',
                'bg_style' => 'solid',
                'layout' => 'heritage'
            ],
            'minimalist_mono' => [
                'primary_color' => '#0f172a',
                'secondary_color' => '#f59e0b',
                'bg_style' => 'solid',
                'layout' => 'minimalist'
            ],
            'minimalist_blue' => [
                'primary_color' => '#1e40af',
                'secondary_color' => '#60a5fa',
                'bg_style' => 'solid',
                'layout' => 'minimalist'
            ],
            'minimalist_sage' => [
                'primary_color' => '#166534',
                'secondary_color' => '#86efac',
                'bg_style' => 'solid',
                'layout' => 'minimalist'
            ],
            'gazette_classic' => [
                'primary_color' => '#1a237e',
                'secondary_color' => '#f5c518',
                'bg_style' => 'solid',
                'layout' => 'gazette'
            ],
            'gazette_sepia' => [
                'primary_color' => '#78350f',
                'secondary_color' => '#d4a574',
                'bg_style' => 'solid',
                'layout' => 'gazette'
            ],
            'gazette_modern' => [
                'primary_color' => '#0f172a',
                'secondary_color' => '#fbbf24',
                'bg_style' => 'solid',
                'layout' => 'gazette'
            ],
        ];

        return $variations[$template] ?? $variations['default'];
    }

    private function getCardView(string $template): string
    {
        $templateData = $this->getCardTemplateData($template);
        $layout = $templateData['layout'] ?? 'standard';

        $viewMap = [
            'standard' => 'staff.production.card_print',
            'executive' => 'staff.production.card_print_executive',
            'diplomat' => 'staff.production.card_print_diplomat',
            'press' => 'staff.production.card_print_press',
            'heritage' => 'staff.production.card_print_heritage',
            'minimalist' => 'staff.production.card_print_minimalist',
            'gazette' => 'staff.production.card_print_gazette',
        ];

        return $viewMap[$layout] ?? $viewMap['standard'];
    }

    private function getCardBackView(string $template): string
    {
        $templateData = $this->getCardTemplateData($template);
        $layout = $templateData['layout'] ?? 'standard';

        $viewMap = [
            'standard' => 'staff.production.card_print_back',
            'executive' => 'staff.production.card_back_executive',
            'diplomat' => 'staff.production.card_back_diplomat',
            'press' => 'staff.production.card_back_press',
            'heritage' => 'staff.production.card_back_heritage',
            'minimalist' => 'staff.production.card_back_minimalist',
            'gazette' => 'staff.production.card_back_gazette',
        ];

        return $viewMap[$layout] ?? $viewMap['standard'];
    }

    public function generateCertificate(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);

        $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:design_studio_templates,id'],
        ]);

        $template = $this->resolveDesignStudioTemplate($request, $application, DesignStudioTemplate::TYPE_CERTIFICATE);
        if (!$template) {
            return back()->with('error', 'No approved certificate template is available for this application.');
        }

        $from = $application->status;
        $this->ensureRecordCreated($application);

        $generatedDocument = $this->createOrUpdateGeneratedDocument(
            $application,
            $template,
            'registration_certificate'
        );

        // ✅ Use constant to avoid mismatched status string
        ApplicationWorkflow::transition($application, Application::CERT_GENERATED, 'production_generate_certificate');

        $this->recordPrintLog($application, $generatedDocument, 'registration');
        $application->refresh();
        $this->audit('production_generate_certificate', $application, $from, $application->status, [
            'template_id' => $template->id,
            'generated_document_id' => $generatedDocument->id,
        ]);

        return back()->with('success', 'Certificate generated and record created.');
    }

    public function certificatePreview(Application $application)
    {
        $this->ensureCanAccessRegion($application);
        // Also load registrationRecord so preview QR uses the real verification link
        $application->load(['applicant', 'registrationRecord']);

        $edits = (array) ($application->form_data['certificate_edits'] ?? []);

        return view('staff.production.certificate_preview', compact('application', 'edits'));
    }

    public function certificatePrint(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'certificate_payload' => ['required', 'array'],
            'template' => ['nullable', 'string'],
        ]);

        $form = (array) ($application->form_data ?? []);
        $form['certificate_edits'] = $data['certificate_payload'];
        $application->form_data = $form;
        $application->save();

        // Mark generated (workflow state) when user prints
        $from = $application->status;
        if ($application->status !== Application::CERT_GENERATED) {
            $this->ensureRecordCreated($application);
            ApplicationWorkflow::transition($application, Application::CERT_GENERATED, 'production_generate_certificate');
            $application->refresh();
            $this->audit('production_generate_certificate', $application, $from, $application->status);
        }

        $application->load(['applicant', 'registrationRecord']);
        $payload = $data['certificate_payload'];
        $verificationKey = $application->registrationRecord->qr_token
            ?: ($application->registrationRecord->registration_no ?: $application->registrationRecord->record_number);
        $payload['qr_value'] = route('public.verify', $verificationKey ?: 'invalid');
        $payload['reg_no'] = $payload['reg_no'] ?? ($application->registrationRecord->registration_no ?? '—');

        // Always use the single official ZMC certificate template
        $view        = 'staff.production.certificate_mass_media_dust_pink';
        $template_data = [];
        $orientation   = 'portrait';

        return view($view, compact('application', 'payload', 'template_data', 'orientation'));
    }

    private function getTemplateVariationData(string $template): array
    {
        $variations = [
            'var_1' => ['orientation' => 'portrait', 'primary_color' => '#1a237e', 'secondary_color' => '#d4a574', 'bg_type' => 'solid', 'border_style' => 'solid'],
            'var_2' => ['orientation' => 'portrait', 'primary_color' => '#1a1a1a', 'secondary_color' => '#fde68a', 'bg_type' => 'solid', 'border_style' => 'double'],
            'var_3' => ['orientation' => 'portrait', 'primary_color' => '#b71c1c', 'secondary_color' => '#ef9a9a', 'bg_type' => 'gradient', 'border_style' => 'solid'],
            'var_4' => ['orientation' => 'portrait', 'primary_color' => '#4a148c', 'secondary_color' => '#ce93d8', 'bg_type' => 'pattern', 'border_style' => 'ornate'],
            'var_5' => ['orientation' => 'landscape', 'primary_color' => '#1a237e', 'secondary_color' => '#d4a574', 'bg_type' => 'solid', 'border_style' => 'solid'],
            'var_6' => ['orientation' => 'landscape', 'primary_color' => '#311b92', 'secondary_color' => '#b39ddb', 'bg_type' => 'gradient', 'border_style' => 'double'],
            'var_7' => ['orientation' => 'landscape', 'primary_color' => '#004d40', 'secondary_color' => '#80cbc4', 'bg_type' => 'pattern', 'border_style' => 'ornate'],
            'var_8' => ['orientation' => 'portrait', 'primary_color' => '#263238', 'secondary_color' => '#cfd8dc', 'bg_type' => 'solid', 'border_style' => 'solid', 'font_family' => 'Arial, sans-serif'],
            'var_9' => ['orientation' => 'portrait', 'primary_color' => '#bf360c', 'secondary_color' => '#ffab91', 'bg_type' => 'solid', 'border_style' => 'double', 'title_font' => 'Georgia, serif', 'title_style' => 'italic'],
            'var_10' => ['orientation' => 'landscape', 'primary_color' => '#01579b', 'secondary_color' => '#81d4fa', 'bg_type' => 'gradient', 'border_style' => 'solid'],
            'var_11' => ['orientation' => 'portrait', 'primary_color' => '#33691e', 'secondary_color' => '#dcedc8', 'bg_type' => 'solid', 'border_style' => 'ornate'],
            'var_12' => ['orientation' => 'landscape', 'primary_color' => '#e65100', 'secondary_color' => '#ffcc80', 'bg_type' => 'solid', 'border_style' => 'double'],
            'var_13' => ['orientation' => 'portrait', 'primary_color' => '#000000', 'secondary_color' => '#999999', 'bg_type' => 'pattern', 'border_style' => 'solid'],
            'var_14' => ['orientation' => 'landscape', 'primary_color' => '#1a237e', 'secondary_color' => '#ffd54f', 'bg_type' => 'gradient', 'border_style' => 'ornate'],
            'var_15' => ['orientation' => 'portrait', 'primary_color' => '#880e4f', 'secondary_color' => '#f48fb1', 'bg_type' => 'solid', 'border_style' => 'double'],
            'var_16' => ['orientation' => 'landscape', 'primary_color' => '#3e2723', 'secondary_color' => '#bcaaa4', 'bg_type' => 'solid', 'border_style' => 'solid'],
            'var_17' => ['orientation' => 'portrait', 'primary_color' => '#212121', 'secondary_color' => '#d4a574', 'bg_type' => 'gradient', 'border_style' => 'ornate'],
            'var_18' => ['orientation' => 'landscape', 'primary_color' => '#0d47a1', 'secondary_color' => '#bbdefb', 'bg_type' => 'pattern', 'border_style' => 'double'],
            'var_19' => ['orientation' => 'portrait', 'primary_color' => '#ff6f00', 'secondary_color' => '#ffe082', 'bg_type' => 'solid', 'border_style' => 'solid'],
            'var_20' => ['orientation' => 'landscape', 'primary_color' => '#1a1a1a', 'secondary_color' => '#fef3c7', 'bg_type' => 'gradient', 'border_style' => 'ornate'],
            'var_21' => ['orientation' => 'portrait', 'primary_color' => '#006064', 'secondary_color' => '#b2ebf2', 'bg_type' => 'pattern', 'border_style' => 'double'],
            'var_22' => ['orientation' => 'landscape', 'primary_color' => '#4a148c', 'secondary_color' => '#e1bee7', 'bg_type' => 'solid', 'border_style' => 'solid'],
            'var_23' => ['orientation' => 'portrait', 'primary_color' => '#b71c1c', 'secondary_color' => '#ffccbc', 'bg_type' => 'gradient', 'border_style' => 'double'],
            'var_24' => ['orientation' => 'landscape', 'primary_color' => '#263238', 'secondary_color' => '#eceff1', 'bg_type' => 'solid', 'border_style' => 'ornate'],
            'var_25' => ['orientation' => 'portrait', 'primary_color' => '#1a237e', 'secondary_color' => '#c5cae9', 'bg_type' => 'solid', 'border_style' => 'double'],
        ];

        return $variations[$template] ?? $variations['var_1'];
    }

    private function getCertLayoutData(string $template): array
    {
        $layouts = [
            'presidential' => ['orientation' => 'portrait', 'primary_color' => '#1a237e', 'secondary_color' => '#c9a86c'],
            'presidential_green' => ['orientation' => 'portrait', 'primary_color' => '#1a5f2a', 'secondary_color' => '#d4a574'],
            'presidential_maroon' => ['orientation' => 'portrait', 'primary_color' => '#7f1d1d', 'secondary_color' => '#fbbf24'],
            'contemporary' => ['orientation' => 'landscape', 'primary_color' => '#0f172a', 'secondary_color' => '#f59e0b'],
            'contemporary_navy' => ['orientation' => 'landscape', 'primary_color' => '#1a237e', 'secondary_color' => '#60a5fa'],
            'contemporary_slate' => ['orientation' => 'landscape', 'primary_color' => '#334155', 'secondary_color' => '#fbbf24'],
            'heritage_cert' => ['orientation' => 'portrait', 'primary_color' => '#1a5f2a', 'secondary_color' => '#d4a574'],
            'heritage_cert_gold' => ['orientation' => 'portrait', 'primary_color' => '#1a237e', 'secondary_color' => '#f5c518'],
            'heritage_cert_bronze' => ['orientation' => 'portrait', 'primary_color' => '#78350f', 'secondary_color' => '#fbbf24'],
            'elegant' => ['orientation' => 'landscape', 'primary_color' => '#1a237e', 'secondary_color' => '#c9a86c'],
            'elegant_violet' => ['orientation' => 'landscape', 'primary_color' => '#4a148c', 'secondary_color' => '#ce93d8'],
            'elegant_emerald' => ['orientation' => 'landscape', 'primary_color' => '#064e3b', 'secondary_color' => '#6ee7b7'],
        ];

        return $layouts[$template] ?? $layouts['presidential'];
    }

    public function printSingle(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $from = $application->status;

        $template = $this->resolveDesignStudioTemplate($request, $application, $application->application_type === 'registration'
            ? DesignStudioTemplate::TYPE_CERTIFICATE
            : DesignStudioTemplate::TYPE_CARD);

        if (!$template) {
            return back()->with('error', 'No approved design template is available for this application.');
        }

        $generatedDocument = $this->createOrUpdateGeneratedDocument(
            $application,
            $template,
            $application->application_type === 'registration'
                ? 'registration_certificate'
                : 'accreditation_card'
        );

        $generatedDocument->update(['status' => 'printed']);

        $application->update([
            'status' => Application::PRINTED,
            'printed_by' => auth()->id(),
            'printed_at' => now(),
        ]);

        $this->recordPrintLog($application, $generatedDocument, $application->application_type === 'registration' ? 'registration' : 'accreditation');
        $application->refresh();
        $this->audit('production_print_single', $application, $from, $application->status, [
            'generated_document_id' => $generatedDocument->id,
        ]);

        return back()->with('success', 'Print recorded. The item is now available in Issuance & Collection.');
    }

    public function printBatch(Request $request)
    {
        $data = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer'],
        ]);

        $user = Auth::user();
        $isScopedUser = \App\Support\RegionAccessGuard::userIsScoped($user);

        $appsQuery = Application::whereIn('id', $data['application_ids']);
        if ($isScopedUser) {
            $regions = $user->assignedCollectionRegions();
            if (empty($regions)) {
                return back()->with('error', 'You have no assigned regions; nothing to print.');
            }
            $appsQuery->whereIn('collection_region', $regions);
        }
        $apps = $appsQuery->get();

        $processed = 0;

        foreach ($apps as $app) {
            if (
                in_array($app->status, [
                    Application::PRODUCTION_QUEUE,
                    Application::CARD_GENERATED,
                    Application::CERT_GENERATED,
                ], true)
            ) {

                $template = $this->resolveDesignStudioTemplate($request, $app, $app->application_type === 'registration'
                    ? DesignStudioTemplate::TYPE_CERTIFICATE
                    : DesignStudioTemplate::TYPE_CARD);

                if (!$template) {
                    return back()->with('error', 'No approved design template is available for one or more applications.');
                }

                $from = $app->status;
                $generatedDocument = $this->createOrUpdateGeneratedDocument(
                    $app,
                    $template,
                    $app->application_type === 'registration'
                        ? 'registration_certificate'
                        : 'accreditation_card'
                );
                $generatedDocument->update(['status' => 'printed']);

                $app->update([
                    'status' => Application::PRINTED,
                    'printed_by' => auth()->id(),
                    'printed_at' => now(),
                ]);

                $this->recordPrintLog($app, $generatedDocument, $app->application_type === 'registration' ? 'registration' : 'accreditation');
                $app->refresh();
                $this->audit('production_print_batch', $app, $from, $app->status, [
                    'batch' => true,
                    'batch_size' => count($apps),
                    'generated_document_id' => $generatedDocument->id,
                ]);

                $processed++;
            }
        }

        return back()->with('success', "Batch print processed. Printed: {$processed} item(s).");
    }

    public function markIssued(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        if ($application->status !== Application::PRINTED) {
            return back()->with('error', 'Only printed cards or certificates can be issued.');
        }
        $from = $application->status;

        $application->update([
            'status' => Application::ISSUED,
            'issued_by' => auth()->id(),
            'issued_at' => now(),
        ]);
        $application->refresh();
        $this->audit('production_issue', $application, $from, $application->status);

        return back()->with('success', 'Marked as issued.');
    }

    public function designer(Request $request)
    {
        $template = null;
        if ($request->has('template')) {
            $template = CardTemplate::findOrFail($request->template);
        }
        return view('staff.production.designer', compact('template'));
    }

    public function templates()
    {
        $templates = CardTemplate::with('creator')->orderByDesc('created_at')->get();
        return view('staff.production.templates', compact('templates'));
    }

    private function resolveDesignStudioTemplate(Request $request, Application $application, string $type): ?DesignStudioTemplate
    {
        $requestedId = $request->input('template_id');

        $query = DesignStudioTemplate::approved()
            ->where('type', $type)
            ->where(function ($builder) use ($application) {
                if (in_array($application->application_type, ['registration', 'renewal'], true)) {
                    $expectedFor = 'registration';
                } else {
                    $expectedFor = ($application->journalist_scope === 'foreign') ? 'accreditation_foreign' : 'accreditation_local';
                }

                $builder->where('template_for', $expectedFor)
                    ->orWhereNull('template_for');
            });

        if ($requestedId) {
            $query->where('id', $requestedId);
        }

        return $query->orderByDesc('approved_at')
            ->orderByDesc('created_at')
            ->first();
    }

    private function createOrUpdateGeneratedDocument(Application $application, ?DesignStudioTemplate $template, string $documentType): GeneratedDocument
    {
        $record = null;

        if ($application->application_type === 'registration') {
            $record = $application->registrationRecord;
        } else {
            $record = $application->accreditationRecord;
        }

        if (!$record) {
            $this->ensureRecordCreated($application);
            $record = $application->application_type === 'registration'
                ? $application->fresh()->registrationRecord
                : $application->fresh()->accreditationRecord;
        }

        $documentNumber = $record?->registration_no
            ?? $record?->certificate_no
            ?? $application->generateFormattedNumber();

        $qrCodeValue = $record?->qr_token
            ? route('public.verify', $record->qr_token)
            : null;

        $templateId = $template?->id;
        $templateVersionId = $template?->versions()->latest()->first()?->id;

        return GeneratedDocument::updateOrCreate(
            ['application_id' => $application->id, 'document_type' => $documentType],
            [
                'applicant_id' => $application->applicant_user_id,
                'template_id' => $templateId,
                'template_version_id' => $templateVersionId,
                'document_number' => $documentNumber,
                'qr_code_value' => $qrCodeValue,
                'generated_by' => Auth::id(),
                'generated_at' => now(),
                'status' => 'generated',
            ]
        );
    }

    private function recordPrintLog(Application $application, GeneratedDocument $generatedDocument, string $recordType): void
    {
        PrintLog::create([
            'record_type' => $recordType,
            'record_id' => $application->id,
            'application_id' => $application->id,
            'document_type' => $generatedDocument->document_type,
            'generated_document_id' => $generatedDocument->id,
            'printed_by' => Auth::id(),
            'printer_name' => Auth::user()?->name,
            'print_status' => 'printed',
            'print_attempts' => 1,
            'printed_at' => now(),
            'notes' => 'Generated via production workflow and recorded in Design Studio tracking.',
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:card,certificate'],
            'year' => ['required', 'string', 'max:4'],
            'background' => $this->imageUploadRules(5120, ['jpg', 'jpeg', 'png']),
            'layout_config' => ['nullable', 'string'],
        ]);

        $bgPath = null;
        if ($request->hasFile('background')) {
            $bgPath = $this->storePublicFile($request->file('background'), 'card_templates', FileType::safeHashName($request->file('background')));
        }

        $layoutConfig = [];
        if (!empty($data['layout_config'])) {
            $layoutConfig = json_decode($data['layout_config'], true) ?: [];
        }

        CardTemplate::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'year' => $data['year'],
            'background_path' => $bgPath,
            'layout_config' => $layoutConfig,
            'is_active' => false,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('staff.production.templates')->with('success', 'Template created successfully.');
    }

    public function updateTemplate(Request $request, CardTemplate $template)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:card,certificate'],
            'year' => ['required', 'string', 'max:4'],
            'background' => $this->imageUploadRules(5120, ['jpg', 'jpeg', 'png']),
            'layout_config' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('background')) {
            if ($template->background_path) {
                Storage::disk('public')->delete($template->background_path);
            }
            $data['background_path'] = $this->storePublicFile($request->file('background'), 'card_templates', FileType::safeHashName($request->file('background')));
        }

        $layoutConfig = [];
        if (!empty($data['layout_config'])) {
            $layoutConfig = json_decode($data['layout_config'], true) ?: [];
        }

        $template->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'year' => $data['year'],
            'background_path' => $data['background_path'] ?? $template->background_path,
            'layout_config' => $layoutConfig,
        ]);

        return redirect()->route('staff.production.templates')->with('success', 'Template updated successfully.');
    }

    public function activateTemplate(CardTemplate $template)
    {
        if ($template->is_active) {
            $template->update(['is_active' => false]);
            return back()->with('success', "Template \"{$template->name}\" deactivated.");
        }

        CardTemplate::where('type', $template->type)->where('is_active', true)->update(['is_active' => false]);
        $template->update(['is_active' => true]);

        return back()->with('success', "Template \"{$template->name}\" is now active for {$template->type} production.");
    }

    /* helpers */
    private function audit(string $action, Application $application, ?string $from, ?string $to, array $meta = []): void
    {
        $payload = array_merge([
            'actor_role' => session('active_staff_role'),
            'actor_user_id' => Auth::id(),
            'from_status' => $from,
            'to_status' => $to,
        ], $meta);

        ActivityLogger::log($action, $application, $from, $to, $payload);
        \App\Support\AuditTrail::log($action, $application, $payload);
    }

    /**
     * Helper to ensure an AccreditationRecord or RegistrationRecord is created.
     */
    private function ensureRecordCreated(Application $application): void
    {
        $number = $application->generateFormattedNumber();
        $token = Str::random(12);

        if ($application->application_type === 'registration') {
            if (!$application->registrationRecord) {
                RegistrationRecord::create([
                    'contact_user_id' => $application->applicant_user_id,
                    'application_id' => $application->id,
                    'entity_name' => $application->form_data['media_house_name'] ?? ($application->applicant->name ?? 'ZMC Media House'),
                    'registration_no' => $number,
                    'record_number' => $number,
                    'status' => 'active',
                    'issued_at' => now(),
                    'expires_at' => now()->addYears(5), // Certificate valid for 5 years
                    'qr_token' => $token,
                ]);
            }
        } else {
            if (!$application->accreditationRecord) {
                // Accreditation expires on December 31st of the current year
                $expiryDate = now()->endOfYear()->startOfDay()->setTime(23, 59, 59);
                
                AccreditationRecord::create([
                    'holder_user_id' => $application->applicant_user_id,
                    'application_id' => $application->id,
                    'certificate_no' => $number,
                    'record_number' => $number,
                    'status' => 'active',
                    'issued_at' => now(),
                    'expires_at' => $expiryDate, // Expires December 31st of current year
                    'qr_token' => $token,
                ]);
            }
        }
    }
}
