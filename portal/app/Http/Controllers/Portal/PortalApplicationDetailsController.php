<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PortalApplicationDetailsController extends Controller
{
    public function show(Request $request, Application $application)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        // Applicants only access their own applications; staff may access anything.
        if (!$user->hasAnyRole(['accreditation_officer','accounts_payments','registrar','production','super_admin'])) {
            if ((int)$application->applicant_user_id !== (int)$user->id) {
                abort(403);
            }
        }

        $application->loadMissing(['applicant','documents']);

        $fd = $application->form_data;
        if (is_string($fd)) {
            $decoded = json_decode($fd, true);
            $fd = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($fd)) $fd = [];

        if (isset($fd['ap1']) && is_array($fd['ap1'])) {
            foreach ($fd['ap1'] as $key => $value) {
                if (!array_key_exists($key, $fd)) $fd[$key] = $value;
            }
        }

        // Older media-house submissions stored repeatable table fields as flat
        // keys such as Directors[email][0]. Convert those keys before sending
        // the review payload so the preview can render complete rows instead
        // of exposing the raw field names.
        $legacyRows = [
            'directors' => [],
            'senior_managers' => [],
            'managers' => [],
        ];
        foreach (array_keys($fd) as $key) {
            if (!preg_match('/^(directors|senior[ _]managers|managers)\[([^\]]+)\]\[(\d*)\]$/i', (string) $key, $match)) {
                continue;
            }
            $group = str_replace(' ', '_', strtolower($match[1]));
            $field = str_replace(' ', '_', strtolower($match[2]));
            $index = $match[3] === '' ? null : (int) $match[3];
            $value = $fd[$key];
            if ($index === null && is_array($value)) {
                foreach ($value as $valueIndex => $item) {
                    $legacyRows[$group][(int) $valueIndex][$field] = $item;
                }
            } else {
                $legacyRows[$group][$index ?? 0][$field] = $value;
            }
            unset($fd[$key]);
        }
        foreach ($legacyRows as $group => $rows) {
            if ($rows !== [] && !isset($fd[$group])) {
                ksort($rows);
                $fd[$group] = array_values($rows);
            }
        }

        // Older submissions may have stored contact fields under alternate keys,
        // or only on the applicant profile. Normalize the common review fields.
        $profile = $application->applicant?->profile_data ?? [];
        foreach ([
            'email' => ['email', 'email_address', 'contact_email'],
            'phone' => ['phone', 'phone_number', 'cell_number', 'contact_phone'],
            'gender' => ['gender', 'sex'],
        ] as $target => $keys) {
            $value = collect($keys)->map(fn ($key) => $fd[$key] ?? null)->first(fn ($value) => filled($value));
            if (!filled($value)) {
                $value = match ($target) {
                    'email' => $application->applicant?->email,
                    'phone' => $application->applicant?->phone_number,
                    'gender' => $profile['gender'] ?? $profile['sex'] ?? null,
                };
            }
            if (filled($value)) $fd[$target] = $value;
        }

        $isLocal = ($application->journalist_scope === 'local') || (($fd['journalist_scope'] ?? '') === 'local');
        if ($isLocal) {
            $foreignFields = [
                'passport_no', 'passport_expiry', 'passport_issued_at', 
                'first_time_in_zim', 'last_in_zim_when', 'address_in_zimbabwe', 
                'arrived_on', 'arrival_mode', 'port_of_entry', 
                'departing_on', 'special_assignment', 'journalist_based_country'
            ];
            foreach ($foreignFields as $f) {
                unset($fd[$f]);
            }
        }

        $app = $application->toArray();
        $app['applicant_name'] = $application->applicant?->name;
        $app['form_code'] = match ($application->application_type) {
            'accreditation' => 'AP3',
            'registration'  => $application->form_type ?? 'AP1',
            default         => strtoupper((string)$application->application_type),
        };

        // Standardized labels for the frontend
        $labels = [
            'title' => 'Title',
            'surname' => 'Surname',
            'first_name' => 'First Name',
            'other_names' => 'Other Names',
            'dob' => 'Date of Birth',
            'gender' => 'Sex',
            'sex' => 'Sex',
            'birth_place' => 'Place & Country of Birth',
            'origin' => 'Country of Origin',
            'nationality' => 'Nationality',
            'id_passport_number' => 'ID/Passport Number',
            'passport_no' => 'Passport Number',
            'employer_name' => 'Employer/Media House',
            'medium_type' => 'Medium Type',
            'designation' => 'Designation',
            'assignment_brief' => 'Assignment Brief',
            'arrival_date' => 'Arrival Date',
            'departure_date' => 'Departure Date',
            'port_entry' => 'Port of Entry',
            'zim_local_address' => 'Local Address',
            'zim_address' => 'Zimbabwe Address',
            'phone' => 'Phone',
            'email' => 'Email',
            'org_name' => 'Organization Name',
            'reg_no' => 'Registration Number',
            'website' => 'Website',
            'head_office' => 'Head Office Address',
            'postal_address' => 'Postal Address',
            'contact_person' => 'Contact Person',
            'contact_email' => 'Contact Email',
            'contact_phone' => 'Contact Phone',
            'category' => 'Category',
            'operating_model' => 'Operating Model',
            'arrived_on' => 'Arrived On',
            'arrival_mode' => 'Arrival Mode',
            'departing_on' => 'Departing On',
            'special_assignment' => 'Special Assignment',
            'national_reg_no' => 'National ID',
            'marital_status' => 'Marital Status',
            'address' => 'Residential Address',
            'employment_type' => 'Employment Type',
            'media_org' => 'Media Organization',
            'agency_name' => 'News Agency Name',
            'agency_postal_address' => 'Agency Postal Address',
            'agency_physical_address' => 'Agency Physical Address',
            'agency_telephone' => 'Agency Telephone',
            'agency_fax' => 'Agency Fax',
            'agency_email' => 'Agency Email',
            'agency_website' => 'Agency Website',
            'agency_owners' => 'Owner(s)',
            'agency_owners_nationality' => 'Owner Nationality',
            'agency_activities' => 'News Gathering Activities',
            'agency_clients' => 'Media Houses Served',
            'agency_staff_count' => 'Number of Staff',
            'agency_date_established' => 'Date Established',
            'rep_office_name' => 'Representative Office Name',
            'rep_office_email' => 'Representative Office Email',
            'rep_office_address' => 'Zimbabwe Address',
            'rep_office_activities' => 'Activities in Zimbabwe',
            'org_country' => 'Country of Origin',
            'org_owners' => 'Owner(s)',
            'org_owners_nationality' => 'Owner Nationality',
            'org_head_office' => 'Head Office Location',
            'org_mailing_address' => 'Mailing Address',
            'foreign_media_type' => 'Media House Type',
            'contact_name' => 'Contact Name',
            'contact_surname' => 'Contact Surname',
            'contact_address' => 'Contact Address',
            'contact_phone' => 'Contact Phone',
            'contact_email' => 'Contact Email',
            'collection_region' => 'Collection Office',
            'mass_media_category' => 'Mass Media Category',
            'mass_media_activity' => 'Mass Media Activity',
        ];

        // Ensure common fields are at top level for legacy JS if needed, but we'll use fd mostly
        foreach ($labels as $k => $v) {
            if (array_key_exists($k, $fd) && !array_key_exists($k, $app)) {
                $app[$k] = $fd[$k];
            }
        }

        $directors = $fd['directors'] ?? $fd['directors_rows'] ?? [];
        if (is_array($directors) && $directors !== [] && !array_is_list($directors)) {
            $columnCount = max(array_map(fn ($values) => is_array($values) ? count($values) : 1, $directors));
            $rows = [];
            for ($index = 0; $index < $columnCount; $index++) {
                $row = [];
                foreach ($directors as $field => $values) {
                    $row[$field] = is_array($values) ? ($values[$index] ?? null) : ($index === 0 ? $values : null);
                }
                if (collect($row)->filter(fn ($value) => filled($value))->isNotEmpty()) $rows[] = $row;
            }
            $directors = $rows;
        }
        $directors = collect(is_array($directors) ? $directors : [])->map(function ($director) {
            if (!is_array($director)) return $director;
            $director['id_number'] = $director['id_number'] ?? $director['id_passport'] ?? $director['national_id'] ?? null;
            return $director;
        })->values()->all();
        $managers = $fd['managers'] ?? $fd['senior_managers'] ?? $fd['managers_rows'] ?? [];
        if (is_array($managers) && $managers !== [] && !array_is_list($managers)) {
            $columnCount = max(array_map(fn ($values) => is_array($values) ? count($values) : 1, $managers));
            $rows = [];
            for ($index = 0; $index < $columnCount; $index++) {
                $row = [];
                foreach ($managers as $field => $values) {
                    $row[$field] = is_array($values) ? ($values[$index] ?? null) : ($index === 0 ? $values : null);
                }
                if (collect($row)->filter(fn ($value) => filled($value))->isNotEmpty()) $rows[] = $row;
            }
            $managers = $rows;
        }
        $managers = collect(is_array($managers) ? $managers : [])->map(function ($manager) {
            if (!is_array($manager)) return $manager;
            $manager['full_name'] = $manager['full_name'] ?? trim(($manager['name'] ?? '') . ' ' . ($manager['surname'] ?? ''));
            $manager['occupation'] = $manager['occupation'] ?? $manager['position'] ?? null;
            $manager['qualifications'] = $manager['qualifications'] ?? $manager['qualification'] ?? null;
            $manager['address'] = $manager['address'] ?? $manager['manager_address'] ?? null;
            return $manager;
        })->values()->all();

        $documents = $application->documents->map(function ($d) {
            return [
                'document_type' => $d->doc_type ?? $d->document_type ?? 'document',
                'original_name' => $d->original_name ?? null,
                'file_name'     => $d->file_path ?? null,
                'url'           => !empty($d->file_path) ? Storage::disk('public')->url($d->file_path) : null,
                'status'        => $d->status ?? null,
            ];
        })->values();

        $correctionComments = $application->comments()
            ->where('visibility', ApplicationComment::VISIBILITY_APPLICANT)
            ->whereIn('comment_type', ['rejection', 'return', 'fix_request', 'payment_rejected', 'correction_requested', 'payment'])
            ->with('user:id,name')
            ->limit(10)
            ->get(['id', 'user_id', 'comment_type', 'body', 'created_at'])
            ->map(fn ($comment) => [
                'type' => $comment->comment_type,
                'body' => $comment->body,
                'author' => $comment->user?->name,
                'created_at' => $comment->created_at?->format('d/m/Y H:i'),
            ])
            ->values();

        foreach ([
            'correction_notes' => 'Correction requested',
            'proof_review_notes' => 'Payment proof feedback',
        ] as $field => $label) {
            $note = trim((string) ($application->{$field} ?? ''));
            if ($note !== '' && !$correctionComments->contains(fn ($comment) => trim((string) $comment['body']) === $note)) {
                $correctionComments->prepend([
                    'type' => $field === 'proof_review_notes' ? 'payment' : 'correction_requested',
                    'body' => $note,
                    'author' => 'ZMC',
                    'created_at' => $application->updated_at?->format('d/m/Y H:i'),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'application' => $app,
            'form_data' => $fd,
            'labels' => $labels,
            'directors' => $directors,
            'managers' => $managers,
            'documents' => $documents,
            'correction_comments' => $correctionComments,
        ]);
    }
}
