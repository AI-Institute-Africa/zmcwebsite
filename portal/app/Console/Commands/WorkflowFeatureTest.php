<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\ApplicationWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowFeatureTest extends Command
{
    protected $signature = 'system:feature-test {--cleanup : Delete the synthetic test data}';
    protected $description = 'End-to-end test of every workflow action button: officer approve, registrar approve, accounts approve proof, PayNow auto-bypass, complaints, role mgmt.';

    private array $results = [];
    private array $cleanupIds = ['users' => [], 'applications' => [], 'complaints' => [], 'payments' => []];

    public function handle(): int
    {
        $admin = User::role('super_admin')->first();
        if (!$admin) {
            $this->error('Superadmin not found.');
            return 1;
        }
        Auth::login($admin);

        $this->section('1. PROOF-UPLOAD WORKFLOW (Officer → Registrar → Accounts → Production)');
        $this->testProofUploadWorkflow();

        $this->section('2. PAYNOW AUTO-BYPASS (Officer → Registrar [auto-skip Accounts] → Production)');
        $this->testPaynowAutoBypass();

        $this->section('3. PAYNOW NOT-YET-PAID (must NOT bypass Accounts)');
        $this->testPaynowUnverifiedDoesNotBypass();

        $this->section('4. OFFICER REJECT / CORRECTION_REQUESTED');
        $this->testOfficerCorrectionRequest();

        $this->section('5. COMPLAINTS LIFECYCLE (Research/Training)');
        $this->testComplaintsLifecycle();

        $this->section('6. APPEALS LIFECYCLE (Public Info Compliance)');
        $this->testAppealsLifecycle();

        $this->section('7. IT ADMIN ROLE & PERMISSION MANAGEMENT');
        $this->testRoleAndPermissionMgmt();

        $this->section('8. NEW DASHBOARDS RENDER (Research)');
        $this->testNewDashboardsRender();

        if ($this->option('cleanup')) {
            $this->cleanup();
        } else {
            $this->info('  (use --cleanup to remove synthetic data)');
        }

        $this->newLine();
        $passed = count(array_filter($this->results, fn($r) => $r['ok']));
        $failed = count($this->results) - $passed;
        $this->line("=== TOTAL: {$passed} passed, {$failed} failed ===");

        return $failed === 0 ? 0 : 2;
    }

    private function section(string $name): void
    {
        $this->newLine();
        $this->line("==> {$name}");
    }

    private function step(string $name, callable $fn): void
    {
        try {
            $fn();
            $this->results[] = ['name' => $name, 'ok' => true];
            $this->info("   ✓ {$name}");
        } catch (\Throwable $e) {
            $this->results[] = ['name' => $name, 'ok' => false, 'err' => $e->getMessage()];
            $this->error("   ✗ {$name} — " . substr($e->getMessage(), 0, 200));
        }
    }

    private function makeApplicant(string $tag): User
    {
        $u = User::create([
            'name'         => "Test Applicant {$tag}",
            'email'        => 'test+' . $tag . '+' . time() . '@zmc.local',
            'password'     => bcrypt(Str::random(64)),
            'account_type' => 'public',
        ]);
        $this->cleanupIds['users'][] = $u->id;
        return $u;
    }

    private function makeApplication(User $u, array $extra = []): Application
    {
        $app = Application::create(array_merge([
            'applicant_user_id'  => $u->id,
            'application_type'   => 'accreditation',
            'request_type'       => 'new',
            'journalist_scope'   => 'local',
            'status'             => Application::SUBMITTED,
            'current_stage'      => 'submitted',
            'reference'          => 'TST-' . strtoupper(uniqid()),
            'submitted_at'       => now(),
            'payment_status'     => 'none',
            'waiver_status'      => 'none',
            'proof_status'       => 'none',
            'is_draft'           => false,
        ], $extra));
        $this->cleanupIds['applications'][] = $app->id;
        return $app;
    }

    /* === WORKFLOW TESTS === */

    private function testProofUploadWorkflow(): void
    {
        $u = $this->makeApplicant('proof');
        $app = $this->makeApplication($u, ['payment_submission_method' => 'proof_upload']);

        $this->step('Officer approves submitted application', function () use ($app) {
            $r = ApplicationWorkflowService::approveApplication($app->fresh());
            if (!($r->status === Application::FORWARDED_TO_REGISTRAR)) { throw new \RuntimeException("expected forwarded_to_registrar, got {$r->status}"); }
        });

        $this->step('Registrar reviews → moves to REGISTRAR_REVIEW', function () use ($app) {
            $a = $app->fresh();
            ApplicationWorkflow::transition($a, Application::REGISTRAR_REVIEW, 'registrar_open_review');
            if (!($a->fresh()->status === Application::REGISTRAR_REVIEW)) { throw new \RuntimeException("assertion failed"); }
        });

        $this->step('Registrar approves (proof path) → ACCOUNTS_REVIEW', function () use ($app) {
            $r = ApplicationWorkflowService::registrarApprove($app->fresh());
            if (!($r->status === Application::ACCOUNTS_REVIEW)) { throw new \RuntimeException("expected accounts_review, got {$r->status}"); }
        });

        $this->step('Accounts approves proof → PAID_CONFIRMED', function () use ($app) {
            $a = $app->fresh();
            ApplicationWorkflow::transition($a, Application::PAID_CONFIRMED, 'accounts_approve_proof');
            if (!($a->fresh()->status === Application::PAID_CONFIRMED)) { throw new \RuntimeException("assertion failed"); }
        });

        $this->step('Send to production → PRODUCTION_QUEUE', function () use ($app) {
            $a = $app->fresh();
            ApplicationWorkflow::transition($a, Application::PRODUCTION_QUEUE, 'send_to_production');
            if (!($a->fresh()->status === Application::PRODUCTION_QUEUE)) { throw new \RuntimeException("assertion failed"); }
        });
    }

    private function testPaynowAutoBypass(): void
    {
        $u = $this->makeApplicant('paynow');
        $app = $this->makeApplication($u, [
            'payment_submission_method' => 'paynow_reference',
            'payment_status' => 'paid',
        ]);

        // Insert a verified PayNow Payment record so hasSuccessfulPaynowPayment returns true
        try {
            Payment::create([
                'application_id' => $app->id,
                'method' => 'paynow',
                'status' => 'paid',
                'amount' => 50.00,
                'reference' => 'PN-TEST-' . uniqid(),
            ]);
        } catch (\Throwable $e) {
            // continue — payment_status='paid' alone should also trigger bypass
        }

        $this->step('Officer approves PayNow-paid app', function () use ($app) {
            $r = ApplicationWorkflowService::approveApplication($app->fresh());
            if (!($r->status === Application::FORWARDED_TO_REGISTRAR)) { throw new \RuntimeException("assertion failed"); }
        });

        $this->step('Registrar opens review', function () use ($app) {
            $a = $app->fresh();
            ApplicationWorkflow::transition($a, Application::REGISTRAR_REVIEW, 'registrar_open_review');
        });

        $this->step('Registrar approves (PayNow paid) → AUTO PRODUCTION_QUEUE (skips Accounts)', function () use ($app) {
            $r = ApplicationWorkflowService::registrarApprove($app->fresh());
            if ($r->status !== Application::PRODUCTION_QUEUE) {
                throw new \RuntimeException("expected production_queue (auto-bypass), got {$r->status}");
            }
        });
    }

    private function testPaynowUnverifiedDoesNotBypass(): void
    {
        $u = $this->makeApplicant('paynow_unverified');
        $app = $this->makeApplication($u, [
            'payment_submission_method' => 'paynow_reference',
            'payment_status' => 'requested',  // NOT paid
        ]);

        $this->step('Officer approves unverified PayNow', function () use ($app) {
            ApplicationWorkflowService::approveApplication($app->fresh());
        });
        $this->step('Registrar opens review', function () use ($app) {
            $a = $app->fresh();
            ApplicationWorkflow::transition($a, Application::REGISTRAR_REVIEW, 'registrar_open_review');
        });
        $this->step('Registrar approval → ACCOUNTS_REVIEW (must NOT bypass)', function () use ($app) {
            $r = ApplicationWorkflowService::registrarApprove($app->fresh());
            if ($r->status !== Application::ACCOUNTS_REVIEW) {
                throw new \RuntimeException("SECURITY: unverified PayNow bypassed accounts! status={$r->status}");
            }
        });
    }

    private function testOfficerCorrectionRequest(): void
    {
        $u = $this->makeApplicant('correction');
        $app = $this->makeApplication($u);

        $this->step('Officer requests correction → CORRECTION_REQUESTED', function () use ($app) {
            $a = $app->fresh();
            ApplicationWorkflow::transition($a, Application::CORRECTION_REQUESTED, 'officer_request_correction', [
                'notes' => 'Please attach a clearer photo.',
            ]);
            if (!($a->fresh()->status === Application::CORRECTION_REQUESTED)) { throw new \RuntimeException("assertion failed"); }
        });

        $this->step('Applicant resubmits → SUBMITTED', function () use ($app) {
            $a = $app->fresh();
            ApplicationWorkflow::transition($a, Application::SUBMITTED, 'applicant_resubmit');
            if (!($a->fresh()->status === Application::SUBMITTED)) { throw new \RuntimeException("assertion failed"); }
        });
    }

    private function testComplaintsLifecycle(): void
    {
        $c = null;
        $this->step('Citizen creates a complaint', function () use (&$c) {
            $c = Complaint::create([
                'type' => 'complaint',
                'full_name' => 'Test Complainant',
                'email' => 'complainant+' . time() . '@zmc.local',
                'subject' => 'Late issuance of card',
                'message' => 'My card has been delayed for 3 weeks.',
                'status' => 'open',
            ]);
            $this->cleanupIds['complaints'][] = $c->id;
        });

        $this->step('Research/Training moves complaint → in_progress', function () use (&$c) {
            $c->update(['status' => 'in_progress', 'handled_by' => Auth::id()]);
            if (!($c->fresh()->status === 'in_progress')) { throw new \RuntimeException("assertion failed"); }
        });

        $this->step('Research/Training resolves complaint', function () use (&$c) {
            $c->update(['status' => 'resolved', 'handled_at' => now()]);
            if (!($c->fresh()->status === 'resolved')) { throw new \RuntimeException("assertion failed"); }
        });
    }

    private function testAppealsLifecycle(): void
    {
        $a = null;
        $this->step('Citizen lodges appeal (paid service)', function () use (&$a) {
            $a = Complaint::create([
                'type' => 'appeal',
                'full_name' => 'Test Appellant',
                'email' => 'appellant+' . time() . '@zmc.local',
                'subject' => 'Appeal against accreditation rejection',
                'message' => 'I believe my application meets all requirements.',
                'status' => 'open',
            ]);
            $this->cleanupIds['complaints'][] = $a->id;
        });

        $this->step('PR/Public Info Compliance handles appeal', function () use (&$a) {
            $a->update(['status' => 'in_progress', 'handled_by' => Auth::id()]);
            if (!($a->fresh()->status === 'in_progress')) { throw new \RuntimeException("assertion failed"); }
        });
    }

    private function testRoleAndPermissionMgmt(): void
    {
        $this->step('Spatie Role create/delete works', function () {
            $name = 'test_role_' . uniqid();
            $role = \Spatie\Permission\Models\Role::create(['name' => $name]);
            if (!($role->id !== null)) { throw new \RuntimeException("assertion failed"); }
            $role->delete();
        });

        $this->step('Spatie Permission create/sync to role', function () {
            $name = 'test_perm_' . uniqid();
            $rname = 'test_role2_' . uniqid();
            $perm = \Spatie\Permission\Models\Permission::create(['name' => $name]);
            $role = \Spatie\Permission\Models\Role::create(['name' => $rname]);
            $role->givePermissionTo($perm);
            if (!($role->hasPermissionTo($name))) { throw new \RuntimeException("assertion failed"); }
            $role->delete();
            $perm->delete();
        });

        $this->step('Required roles exist in DB', function () {
            $required = ['super_admin','accreditation_officer',
                         'registrar','accounts_payments','it_admin','auditor','director',
                         'pr_officer','public_info_compliance','research_training'];
            foreach ($required as $r) {
                $exists = \Spatie\Permission\Models\Role::where('name', $r)->exists();
                if (!($exists)) { throw new \RuntimeException("missing role: {$r}"); }
            }
        });
    }

    private function testNewDashboardsRender(): void
    {
        $this->step('ResearchTrainingController::dashboard renders', function () {
            $c = app(\App\Http\Controllers\Staff\ResearchTrainingController::class);
            $r = $c->dashboard(request());
            if (!($r !== null)) { throw new \RuntimeException("assertion failed"); }
        });
    }

    private function cleanup(): void
    {
        DB::transaction(function () {
            DB::table('payments')->whereIn('application_id', $this->cleanupIds['applications'])->delete();
            DB::table('complaints')->whereIn('id', $this->cleanupIds['complaints'])->delete();
            DB::table('applications')->whereIn('id', $this->cleanupIds['applications'])->delete();
            DB::table('users')->whereIn('id', $this->cleanupIds['users'])->delete();
        });
        $this->info('  Cleaned up synthetic data.');
    }
}
