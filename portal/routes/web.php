<?php

use Illuminate\Support\Facades\Route;

// System health (used by IT Admin Dashboard uptime indicator)
Route::get('/health', \App\Http\Controllers\HealthController::class)->name('system.health');
use Illuminate\Http\Request;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PortalController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;

use App\Http\Controllers\AccreditationPortalController;
use App\Http\Controllers\MediaHousePortalController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\Portal\PortalApplicationDetailsController;

use App\Http\Controllers\Staff\StaffAuthController;
use App\Http\Controllers\Staff\AccreditationOfficerController;
use App\Http\Controllers\Staff\AccountsPaymentsController;
use App\Http\Controllers\Staff\RegistrarController;
use App\Http\Controllers\Staff\ProductionController;
use App\Http\Controllers\Staff\ApplicationDetailsController;
use App\Http\Controllers\Staff\ItAdminController;
use App\Http\Controllers\Staff\ItDashboardController;
use App\Http\Controllers\Staff\AuditorController;
use App\Http\Controllers\Staff\DirectorController;
use App\Http\Controllers\Staff\FormBuilderController;
use App\Http\Controllers\Staff\DashboardBuilderController;
use App\Http\Controllers\Staff\RolesPermissionsController;
use App\Http\Controllers\Staff\FoiaAppealsController;

use App\Http\Controllers\Admin\UserApprovalController;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminApplicationsController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\AdminSystemController;
use App\Http\Controllers\Admin\SuperAdminConfigController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\DownloadsController;
use App\Http\Controllers\Admin\ComplaintsController;
use App\Http\Controllers\SettingsController;

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaynowController;

/*
|--------------------------------------------------------------------------
| PUBLIC LANDING ( / ) -> home.blade.php
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('public.home');

/*
|--------------------------------------------------------------------------
| PUBLIC PORTAL SELECTION
|--------------------------------------------------------------------------
| Stores public portal choice then routes user to login/signup.
*/
Route::post('/choose-portal', function (Request $request) {
    $data = $request->validate([
        'portal' => ['required', 'in:journalist,mass_media'],
        'action' => ['sometimes', 'in:login,register'],
    ]);

    $request->session()->put('public_selected_portal', $data['portal']);
    $action = $data['action'] ?? 'login';

    if ($data['portal'] === 'journalist') {
        return redirect()->route($action === 'register' ? 'media_practitioner.register' : 'media_practitioner.login');
    }

    return redirect()->route($action === 'register' ? 'media_house.register' : 'media_house.login');
})->name('public.choose_portal');

/*
|--------------------------------------------------------------------------
| STAFF AUTH
|--------------------------------------------------------------------------
| /staff goes directly to staff login (no role selection).
| OTP verification after password. Account activation via token link.
*/
Route::get('/staff', [StaffAuthController::class, 'show'])->name('staff.entry');
Route::get('/staff/login', [StaffAuthController::class, 'show'])->name('staff.login');
Route::post('/staff/login', [StaffAuthController::class, 'login'])->middleware('throttle:5,1')->name('staff.login.store');
Route::middleware('auth')->group(function () {
    Route::get('/staff/switch-dashboard', [\App\Http\Controllers\Staff\RoleSelectController::class, 'index'])->name('staff.roles.switch');
    Route::post('/staff/switch-dashboard', [\App\Http\Controllers\Staff\RoleSelectController::class, 'switchToRole'])->name('staff.roles.switch.store');
});
Route::post('/staff/choose-role', function (\Illuminate\Http\Request $request) {
    return redirect()->route('staff.login');
})->name('staff.choose_role');
Route::post('/staff/logout', [StaffAuthController::class, 'logout'])->middleware('auth')->name('staff.logout');

Route::get('/staff/otp', [StaffAuthController::class, 'showOtp'])->name('staff.otp.show');
Route::post('/staff/otp', [StaffAuthController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('staff.otp.verify');
Route::post('/staff/otp/resend', [StaffAuthController::class, 'resendOtp'])->middleware('throttle:3,5')->name('staff.otp.resend');

Route::get('/staff/activate/{token}', [StaffAuthController::class, 'showActivation'])->name('staff.activate');
Route::post('/staff/activate/{token}', [StaffAuthController::class, 'activate'])->name('staff.activate.store');

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH (GUEST)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/media-practitioner-login', [LoginController::class, 'showLoginForm'])->name('media_practitioner.login');
    Route::post('/media-practitioner-login', [LoginController::class, 'login'])->name('media_practitioner.login.store');

    Route::get('/media-practitioner-signup', [RegisterController::class, 'showRegistrationForm'])->name('media_practitioner.register');
    Route::post('/media-practitioner-signup', [RegisterController::class, 'store'])->name('media_practitioner.register.store');

    Route::get('/media-house-login', [LoginController::class, 'showLoginForm'])->name('media_house.login');
    Route::post('/media-house-login', [LoginController::class, 'login'])->name('media_house.login.store');

    Route::get('/media-house-signup', [RegisterController::class, 'showRegistrationForm'])->name('media_house.register');
    Route::post('/media-house-signup', [RegisterController::class, 'store'])->name('media_house.register.store');

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('auth.login.store');

    Route::get('/signup', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/signup', [RegisterController::class, 'store'])->name('auth.register.store');

    Route::get('/email/verify', [RegisterController::class, 'showVerificationNotice'])->name('verification.notice');
    Route::get('/email/verify/{token}', [RegisterController::class, 'verifyEmail'])->name('verification.verify')->middleware('throttle:6,1');
    Route::post('/email/resend', [RegisterController::class, 'resendVerification'])->name('verification.resend')->middleware('throttle:3,1');

    // Password reset (public + staff can use)
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});


/*
|--------------------------------------------------------------------------
| PAYNOW CALLBACKS (PUBLIC - No Auth Required)
|--------------------------------------------------------------------------
*/
Route::post('/paynow/callback', [\App\Http\Controllers\Portal\PaynowController::class, 'callback'])
    ->name('paynow.callback');
Route::get('/paynow/return', [\App\Http\Controllers\Portal\PaynowController::class, 'return'])
    ->name('paynow.return');

/*
|--------------------------------------------------------------------------
| PUBLIC CONTENT API (EXTERNAL)
|--------------------------------------------------------------------------
*/
Route::post('/api/public/subscribe', [\App\Http\Controllers\Api\SubscriberApiController::class, 'store']);
Route::get('/api/v1/external-content', [\App\Http\Controllers\Api\PublicContentController::class, 'getContent'])->name('api.v1.external-content');

Route::get('/api/v1/notices/{id}', [\App\Http\Controllers\Api\PublicContentController::class, 'showNotice']);
Route::get('/api/v1/events/{id}', [\App\Http\Controllers\Api\PublicContentController::class, 'showEvent']);

// Downloads / Documents & Reports (managed by PR Officer)
Route::get('/api/v1/media', [\App\Http\Controllers\Api\WebsiteApiController::class, 'media'])->name('api.v1.media');
Route::get('/api/v1/media/{id}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'mediaItem'])->name('api.v1.media.show');
Route::get('/api/v1/offices', [\App\Http\Controllers\Api\WebsiteApiController::class, 'offices'])->name('api.v1.offices');
Route::get('/api/v1/services', [\App\Http\Controllers\Api\WebsiteApiController::class, 'services'])->name('api.v1.services');
Route::get('/api/v1/website-pages/{page}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'peoplePage'])
    ->whereIn('page', ['commissioners', 'secretariat'])
    ->name('api.v1.website-pages.show');

// FOIA Appeals from website → routed to portal complaints dashboard
// Payment proof → Accounts Officer approval queue
Route::post('/api/v1/appeals', [\App\Http\Controllers\Api\PublicContentController::class, 'submitAppeal'])->middleware('throttle:5,1')->name('api.v1.appeals.store');
Route::post('/api/v1/complaints', [\App\Http\Controllers\Api\PublicContentController::class, 'submitComplaint'])->middleware('throttle:5,1')->name('api.v1.complaints.store');
Route::options('/api/v1/appeals', [\App\Http\Controllers\Api\PublicContentController::class, 'preflight']);
Route::options('/api/v1/complaints', [\App\Http\Controllers\Api\PublicContentController::class, 'preflight']);

// FOIA Appeals PayNow Payment Routes (Public - No Auth Required)
Route::post('/api/v1/appeals/payment/initiate', [\App\Http\Controllers\Portal\PaynowController::class, 'initiateAppealPayment'])
    ->middleware('throttle:5,1')
    ->name('api.v1.appeals.payment.initiate');
Route::post('/api/v1/appeals/payment/status', [\App\Http\Controllers\Portal\PaynowController::class, 'checkAppealPaymentStatus'])
    ->middleware('throttle:15,1')
    ->name('api.v1.appeals.payment.status');
Route::get('/appeals/payment/return', [\App\Http\Controllers\Portal\PaynowController::class, 'appealPaymentReturn'])
    ->name('appeals.payment.return');
Route::options('/api/v1/appeals/payment/initiate', [\App\Http\Controllers\Api\PublicContentController::class, 'preflight']);
Route::options('/api/v1/appeals/payment/status', [\App\Http\Controllers\Api\PublicContentController::class, 'preflight']);
Route::post('/api/v1/appeals/payment/proof', [\App\Http\Controllers\Api\PublicContentController::class, 'uploadAppealPaymentProof'])
    ->middleware('throttle:5,1')
    ->name('api.v1.appeals.payment.proof');
Route::options('/api/v1/appeals/payment/proof', [\App\Http\Controllers\Api\PublicContentController::class, 'preflight']);

// Dedicated complaints dashboard. The old admin complaints route is retired.
Route::middleware(['staff.portal', 'role:complaints_officer|research_training|super_admin|director'])
    ->get('/complaints', fn () => redirect()->route('staff.research.dashboard'))
    ->name('complaints.dashboard');

/*
|--------------------------------------------------------------------------
| PR OFFICER CONTENT MANAGEMENT API — AUTHENTICATED
|--------------------------------------------------------------------------
| Authenticated via Laravel Sanctum (Bearer token).
| Login:  POST /api/v1/pr/login  →  returns { token }
| Use token in all subsequent requests:  Authorization: Bearer <token>
|
| Roles allowed: pr_officer, super_admin
*/
Route::post('/api/v1/pr/login',  [\App\Http\Controllers\Api\PrOfficerApiController::class, 'login']);
Route::post('/api/v1/pr/logout', [\App\Http\Controllers\Api\PrOfficerApiController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'ability:pr-officer'])->prefix('api/v1/pr')->group(function () {

    // Stats & subscribers (read)
    Route::get('stats',       [\App\Http\Controllers\Api\PrOfficerApiController::class, 'stats']);
    Route::get('subscribers', [\App\Http\Controllers\Api\PrOfficerApiController::class, 'subscribers']);

    // News
    Route::get   ('news',              [\App\Http\Controllers\Api\PrOfficerApiController::class, 'indexNews']);
    Route::post  ('news',              [\App\Http\Controllers\Api\PrOfficerApiController::class, 'storeNews']);
    Route::get   ('news/{news}',       [\App\Http\Controllers\Api\PrOfficerApiController::class, 'showNews']);
    Route::put   ('news/{news}',       [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateNews']);
    Route::patch ('news/{news}',       [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateNews']);
    Route::delete('news/{news}',       [\App\Http\Controllers\Api\PrOfficerApiController::class, 'destroyNews']);
    Route::patch ('news/{news}/publish',[\App\Http\Controllers\Api\PrOfficerApiController::class, 'publishNews']);

    // Notices
    Route::get   ('notices',                 [\App\Http\Controllers\Api\PrOfficerApiController::class, 'indexNotices']);
    Route::post  ('notices',                 [\App\Http\Controllers\Api\PrOfficerApiController::class, 'storeNotice']);
    Route::get   ('notices/{notice}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'showNotice']);
    Route::put   ('notices/{notice}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateNotice']);
    Route::patch ('notices/{notice}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateNotice']);
    Route::delete('notices/{notice}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'destroyNotice']);
    Route::patch ('notices/{notice}/publish',[\App\Http\Controllers\Api\PrOfficerApiController::class, 'publishNotice']);

    // Events
    Route::get   ('events',                [\App\Http\Controllers\Api\PrOfficerApiController::class, 'indexEvents']);
    Route::post  ('events',                [\App\Http\Controllers\Api\PrOfficerApiController::class, 'storeEvent']);
    Route::get   ('events/{event}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'showEvent']);
    Route::put   ('events/{event}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateEvent']);
    Route::patch ('events/{event}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateEvent']);
    Route::delete('events/{event}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'destroyEvent']);
    Route::patch ('events/{event}/publish',[\App\Http\Controllers\Api\PrOfficerApiController::class, 'publishEvent']);

    // Vacancies
    Route::get   ('vacancies',                   [\App\Http\Controllers\Api\PrOfficerApiController::class, 'indexVacancies']);
    Route::post  ('vacancies',                   [\App\Http\Controllers\Api\PrOfficerApiController::class, 'storeVacancy']);
    Route::get   ('vacancies/{vacancy}',         [\App\Http\Controllers\Api\PrOfficerApiController::class, 'showVacancy']);
    Route::put   ('vacancies/{vacancy}',         [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateVacancy']);
    Route::patch ('vacancies/{vacancy}',         [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateVacancy']);
    Route::delete('vacancies/{vacancy}',         [\App\Http\Controllers\Api\PrOfficerApiController::class, 'destroyVacancy']);
    Route::patch ('vacancies/{vacancy}/publish', [\App\Http\Controllers\Api\PrOfficerApiController::class, 'publishVacancy']);

    // Tenders
    Route::get   ('tenders',                 [\App\Http\Controllers\Api\PrOfficerApiController::class, 'indexTenders']);
    Route::post  ('tenders',                 [\App\Http\Controllers\Api\PrOfficerApiController::class, 'storeTender']);
    Route::get   ('tenders/{tender}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'showTender']);
    Route::put   ('tenders/{tender}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateTender']);
    Route::patch ('tenders/{tender}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateTender']);
    Route::delete('tenders/{tender}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'destroyTender']);
    Route::patch ('tenders/{tender}/publish',[\App\Http\Controllers\Api\PrOfficerApiController::class, 'publishTender']);

    // Downloads / Documents
    Route::get   ('downloads',                   [\App\Http\Controllers\Api\PrOfficerApiController::class, 'indexDownloads']);
    Route::post  ('downloads',                   [\App\Http\Controllers\Api\PrOfficerApiController::class, 'storeDownload']);
    Route::get   ('downloads/{download}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'showDownload']);
    Route::put   ('downloads/{download}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateDownload']);
    Route::patch ('downloads/{download}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'updateDownload']);
    Route::delete('downloads/{download}',        [\App\Http\Controllers\Api\PrOfficerApiController::class, 'destroyDownload']);

    // Gallery photographs and video references/uploads
    Route::get   ('media',                [\App\Http\Controllers\Api\MediaLibraryApiController::class, 'index']);
    Route::post  ('media',                [\App\Http\Controllers\Api\MediaLibraryApiController::class, 'store']);
    Route::get   ('media/{media}',        [\App\Http\Controllers\Api\MediaLibraryApiController::class, 'show']);
    Route::put   ('media/{media}',        [\App\Http\Controllers\Api\MediaLibraryApiController::class, 'update']);
    Route::patch ('media/{media}',        [\App\Http\Controllers\Api\MediaLibraryApiController::class, 'update']);
    Route::patch ('media/{media}/publish',[\App\Http\Controllers\Api\MediaLibraryApiController::class, 'publish']);
    Route::delete('media/{media}',        [\App\Http\Controllers\Api\MediaLibraryApiController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| ZMC WEBSITE API — EXTENDED ENDPOINTS
|--------------------------------------------------------------------------
| Consumed by the configured public website for:
|   - API health check
|   - Accreditation card / licence verification (public)
|   - Media house registration verification (public)
|   - Public directories of practitioners & media houses
|   - Contact Us form submission from the website
*/

// Health / Status
Route::get('/api/v1/status', [\App\Http\Controllers\Api\WebsiteApiController::class, 'status'])
    ->name('api.v1.status');

// Verification endpoints
Route::get('/api/v1/verify/accreditation/{number}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'verifyAccreditation'])
    ->name('api.v1.verify.accreditation');
Route::get('/api/v1/verify/registration/{number}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'verifyRegistration'])
    ->name('api.v1.verify.registration');

// Public directory endpoints
Route::get('/api/v1/accredited-practitioners', [\App\Http\Controllers\Api\WebsiteApiController::class, 'accreditedPractitioners'])
    ->name('api.v1.accredited-practitioners');
Route::get('/api/v1/registered-mediahouses', [\App\Http\Controllers\Api\WebsiteApiController::class, 'registeredMediahouses'])
    ->name('api.v1.registered-mediahouses');

// Contact Us form
Route::post('/api/v1/contact', [\App\Http\Controllers\Api\WebsiteApiController::class, 'contact'])
    ->name('api.v1.contact');

// PR Content Endpoints
Route::get('/api/v1/vacancies', [\App\Http\Controllers\Api\WebsiteApiController::class, 'vacancies']);
Route::get('/api/v1/vacancies/{id}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'vacancy']);
Route::get('/api/v1/news', [\App\Http\Controllers\Api\WebsiteApiController::class, 'news']);
Route::get('/api/v1/news/{id}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'newsItem']);
Route::get('/api/v1/notices', [\App\Http\Controllers\Api\WebsiteApiController::class, 'notices']);
Route::get('/api/v1/tenders', [\App\Http\Controllers\Api\WebsiteApiController::class, 'tenders']);
Route::get('/api/v1/tenders/{id}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'tender'])->whereNumber('id');
Route::get('/api/v1/events', [\App\Http\Controllers\Api\WebsiteApiController::class, 'events']);
Route::get('/api/v1/downloads', [\App\Http\Controllers\Api\WebsiteApiController::class, 'downloads']);

Route::options('/api/v1/vacancies', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/vacancies/{id}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/news', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/news/{id}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/notices', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/tenders', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/tenders/{id}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight'])->whereNumber('id');
Route::options('/api/v1/events', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/downloads', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/media', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/media/{id}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/offices', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/services', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/website-pages/{page}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight'])
    ->whereIn('page', ['commissioners', 'secretariat']);

// CORS preflight for new endpoints
Route::options('/api/v1/status',                       [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/verify/accreditation/{number}',[\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/verify/registration/{number}', [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/accredited-practitioners',     [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/registered-mediahouses',       [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);
Route::options('/api/v1/contact',                      [\App\Http\Controllers\Api\WebsiteApiController::class, 'preflight']);

// Public content detail pages. The website links notices to /notices/{id};
// keep this outside the applicant/staff middleware so the links are shareable.
Route::get('/notices/{notice}', [\App\Http\Controllers\Website\PublicContentController::class, 'showNotice'])
    ->whereNumber('notice')
    ->name('website.notice.show');
Route::get('/events/{event}', [\App\Http\Controllers\Website\PublicContentController::class, 'showEvent'])
    ->whereNumber('event')
    ->name('website.event.show');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Unified Settings (staff + public)
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'edit'])->name('settings');
    Route::post('/settings/profile', [\App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [\App\Http\Controllers\SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/theme', [\App\Http\Controllers\SettingsController::class, 'updateTheme'])->name('settings.theme');
    Route::post('/settings/theme/ajax', [\App\Http\Controllers\SettingsController::class, 'updateThemeAjax'])->name('settings.theme.ajax');
    Route::get('/settings/profile-photo', [\App\Http\Controllers\SettingsController::class, 'profilePhoto'])->name('settings.profile-photo');
    Route::post('/settings/security', [\App\Http\Controllers\SettingsController::class, 'updateSecurity'])->name('settings.security');
    Route::post('/settings/notifications', [\App\Http\Controllers\SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('/portal/switch-profile', [\App\Http\Controllers\MiscRoutesController::class, 'switchApplicantProfile'])->name('portal.switch-profile');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | AFTER LOGIN ROUTER
    |--------------------------------------------------------------------------
    | routes user into portal chosen on public landing page
    */
    Route::get('/home', function (Request $request) {

        $selected = $request->session()->get('public_selected_portal');

        if ($selected === 'journalist') {
            $request->session()->forget('public_selected_portal');
            return redirect()->route('accreditation.home');
        }

        if ($selected === 'mass_media') {
            $request->session()->forget('public_selected_portal');
            return redirect()->route('mediahouse.portal');
        }

        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('media_house')) {
            return redirect()->route('mediahouse.portal');
        }
        return redirect()->route('accreditation.portal');

    })->name('home');
});

/*
|--------------------------------------------------------------------------
| PUBLIC PORTAL LANDING PAGE (no auth required)
|--------------------------------------------------------------------------
*/
Route::get('/portal', [PortalController::class, 'index'])->name('portal');
Route::post('/notifications/mark-read', [PortalController::class, 'markNotificationsRead'])->name('notifications.markRead');
Route::post('/notifications/{id}/mark-read', [PortalController::class, 'markNotificationRead'])->name('notifications.markOne');

/*
|--------------------------------------------------------------------------
| JOURNALIST PORTAL (APPLICANT)
|--------------------------------------------------------------------------
*/
Route::prefix('portal/accreditation')
    ->middleware(['auth', 'applicant.portal', 'enforce.accreditation.portal'])
    ->name('accreditation.')
    ->group(function () {

        Route::get('/', [AccreditationPortalController::class, 'index'])->name('portal');
        Route::get('/dashboard', [AccreditationPortalController::class, 'dashboard'])->name('home');

            Route::get('/new', [AccreditationPortalController::class, 'new'])->name('new');
            // Correction flow: applicant edits and resubmits
            Route::get('/applications/{application}/edit', [AccreditationPortalController::class, 'editCorrection'])
                ->name('applications.edit');
            Route::post('/applications/{application}/resubmit', [AccreditationPortalController::class, 'resubmitCorrection'])
                ->name('applications.resubmit');
            Route::post('/applications/{application}/amendment-request', [AccreditationPortalController::class, 'requestAmendment'])
                ->name('applications.amendment-request');
            Route::post('/save-draft', [AccreditationPortalController::class, 'saveDraft'])->name('saveDraft');
            Route::post('/submit', [AccreditationPortalController::class, 'submit'])->name('submit');
            Route::delete('/applications/{application}', [AccreditationPortalController::class, 'deleteDraft'])->name('delete_draft');
            Route::post('/applications/{application}/withdraw', [AccreditationPortalController::class, 'withdraw'])->name('withdraw');
            Route::delete('/documents/{document}', [AccreditationPortalController::class, 'deleteDocument'])->name('documents.delete');
            Route::post('/certificates/validate', [AccreditationPortalController::class, 'validateCertificate'])->name('certificates.validate');
            
            // AI-Powered Document Validation
            Route::post('/validate-document', [\App\Http\Controllers\Portal\AI\DocumentValidationController::class, 'validateDocument'])->name('documents.ai-validate');
            Route::get('/document-references', [\App\Http\Controllers\Portal\AI\DocumentValidationController::class, 'getDocumentReferences'])->name('documents.references');

            // AP5 — separate renewal / replacement
            Route::get('/renewal', [AccreditationPortalController::class, 'renewalForm'])->name('renewal');
            Route::get('/replacement', [AccreditationPortalController::class, 'replacementForm'])->name('replacement');
            Route::get('/renewals', [AccreditationPortalController::class, 'renewals'])->name('renewals');
            Route::post('/renewals/save-draft', [AccreditationPortalController::class, 'saveDraftAp5'])->name('renewals.saveDraft');
            Route::post('/renewals/submit', [AccreditationPortalController::class, 'submitAp5'])->name('submitAp5');
            Route::post('/renewals/lookup-practitioner', [AccreditationPortalController::class, 'lookupPractitioner'])->name('lookupPractitioner');

            // Lookup by accreditation number for renewals/replacements
            Route::get('/lookup/{accreditationNumber}', [AccreditationPortalController::class, 'lookupAccreditation'])
                ->name('lookup');

            Route::post('/reminders/{reminder}/acknowledge', [AccreditationPortalController::class, 'acknowledgeReminder'])->name('reminders.acknowledge');

            Route::get('/payments', [AccreditationPortalController::class, 'payments'])->name('payments');
            Route::get('/notices', [AccreditationPortalController::class, 'notices'])->name('notices');
            Route::get('/howto', [AccreditationPortalController::class, 'howto'])->name('howto');
            Route::get('/requirements', [AccreditationPortalController::class, 'requirements'])->name('requirements');
            Route::get('/profile', [AccreditationPortalController::class, 'profile'])->name('profile');
            Route::post('/profile', [AccreditationPortalController::class, 'updateProfile'])->name('profile.update');
            Route::get('/settings', [AccreditationPortalController::class, 'settings'])->name('settings');

            // Downloads
            Route::get('/downloads', [\App\Http\Controllers\Portal\DownloadsController::class, 'index'])
                ->name('downloads');
            Route::get('/downloads/file/{doc}', [\App\Http\Controllers\Portal\DownloadsController::class, 'download'])
                ->name('downloads.file');

            // Renewals (Journalist)
            Route::get('/renewals', [\App\Http\Controllers\Portal\RenewalController::class, 'index'])
                ->name('renewals.index');
            Route::get('/renewals/select-type', [\App\Http\Controllers\Portal\RenewalController::class, 'selectType'])
                ->name('renewals.select-type');
            Route::post('/renewals/select-type', [\App\Http\Controllers\Portal\RenewalController::class, 'storeType'])
                ->name('renewals.store-type');
            Route::get('/renewals/{renewal}/lookup', [\App\Http\Controllers\Portal\RenewalController::class, 'lookup'])
                ->name('renewals.lookup');
            Route::post('/renewals/{renewal}/lookup', [\App\Http\Controllers\Portal\RenewalController::class, 'performLookup'])
                ->name('renewals.perform-lookup');
            Route::get('/renewals/{renewal}/confirm', [\App\Http\Controllers\Portal\RenewalController::class, 'confirm'])
                ->name('renewals.confirm');
            Route::post('/renewals/{renewal}/confirm-no-changes', [\App\Http\Controllers\Portal\RenewalController::class, 'confirmNoChanges'])
                ->name('renewals.confirm-no-changes');
            Route::post('/renewals/{renewal}/submit-changes', [\App\Http\Controllers\Portal\RenewalController::class, 'submitChanges'])
                ->name('renewals.submit-changes');
            Route::get('/renewals/{renewal}/payment', [\App\Http\Controllers\Portal\RenewalController::class, 'payment'])
                ->name('renewals.payment');
            Route::post('/renewals/{renewal}/payment/paynow', [\App\Http\Controllers\Portal\RenewalController::class, 'submitPaynow'])
                ->name('renewals.payment.paynow');
            Route::post('/renewals/{renewal}/payment/proof', [\App\Http\Controllers\Portal\RenewalController::class, 'submitProof'])
                ->name('renewals.payment.proof');
            Route::get('/renewals/{renewal}', [\App\Http\Controllers\Portal\RenewalController::class, 'show'])
                ->name('renewals.show');
        });

    /*
    |--------------------------------------------------------------------------
    | MASS MEDIA PORTAL (APPLICANT)
    |--------------------------------------------------------------------------
    */
    Route::prefix('media-house/registration')
        ->middleware(['auth', 'applicant.portal', 'enforce.media.practitioner.portal'])
        ->name('mediahouse.')
        ->group(function () {

            Route::get('/', [MediaHousePortalController::class, 'dashboard'])->name('portal');

            Route::get('/new', [MediaHousePortalController::class, 'selectRegistrationType'])->name('new');
            Route::get('/new/ap1', [MediaHousePortalController::class, 'newRegistration'])->name('ap1.create');
            Route::get('/new/ap2', [MediaHousePortalController::class, 'ap2Create'])->name('ap2.create');
            Route::get('/new/ap4', [MediaHousePortalController::class, 'ap4Create'])->name('ap4.create');

            Route::get('/levy', [MediaHousePortalController::class, 'levyIndex'])->name('levy.index');
            Route::get('/levy/new', [MediaHousePortalController::class, 'levyCreate'])->name('levy.create');
            Route::post('/levy', [MediaHousePortalController::class, 'levyStore'])->name('levy.store');
            Route::post('/levy/paynow', [\App\Http\Controllers\Portal\PaynowController::class, 'initiateLevy'])->name('levy.paynow');
            // Correction flow: applicant edits and resubmits
            Route::get('/applications/{application}/edit', [MediaHousePortalController::class, 'editCorrection'])->name('applications.edit');
            Route::post('/applications/{application}/resubmit', [MediaHousePortalController::class, 'resubmitCorrection'])->name('applications.resubmit');
            Route::post('/save-draft', [MediaHousePortalController::class, 'saveDraft'])->name('saveDraft');
            Route::post('/submit', [MediaHousePortalController::class, 'submit'])->name('submit');
            Route::delete('/applications/{application}', [MediaHousePortalController::class, 'deleteDraft'])->name('delete_draft');
            Route::post('/applications/{application}/withdraw', [MediaHousePortalController::class, 'withdraw'])->name('withdraw');
            Route::delete('/documents/{document}', [MediaHousePortalController::class, 'deleteDocument'])->name('documents.delete');

            // Staff Management (Linking Journalists)
            Route::get('/staff-members', [\App\Http\Controllers\MediaHouseStaffController::class, 'index'])->name('staff.index');
            Route::post('/staff-members/link', [\App\Http\Controllers\MediaHouseStaffController::class, 'link'])->name('staff.link');
            Route::delete('/staff-members/{staff}', [\App\Http\Controllers\MediaHouseStaffController::class, 'unlink'])->name('staff.unlink');

            // NEW RENEWAL FLOW (AP5) - Number-Only Lookup
            Route::get('/renewals', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'index'])
                ->name('renewals.index');
            Route::get('/renewals/start/{type}', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'start'])
                ->whereIn('type', ['renewal', 'replacement'])
                ->name('renewals.start');
            Route::get('/renewals/select-type', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'selectType'])
                ->name('renewals.select-type');
            Route::post('/renewals/select-type', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'storeType'])
                ->name('renewals.store-type');
            Route::get('/renewals/{renewal}/lookup', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'lookup'])
                ->name('renewals.lookup');
            Route::post('/renewals/{renewal}/lookup', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'performLookup'])
                ->name('renewals.perform-lookup');
            Route::get('/renewals/{renewal}/confirm', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'confirm'])
                ->name('renewals.confirm');
            Route::post('/renewals/{renewal}/confirm-no-changes', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'confirmNoChanges'])
                ->name('renewals.confirm-no-changes');
            Route::post('/renewals/{renewal}/submit-changes', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'submitChanges'])
                ->name('renewals.submit-changes');
            Route::get('/renewals/{renewal}/payment', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'payment'])
                ->name('renewals.payment');
            Route::post('/renewals/{renewal}/payment/paynow', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'submitPaynow'])
                ->name('renewals.payment.paynow');
            Route::post('/renewals/{renewal}/payment/proof', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'submitProof'])
                ->name('renewals.payment.proof');
            Route::get('/renewals/{renewal}', [\App\Http\Controllers\Portal\MediaHouseRenewalController::class, 'show'])
                ->name('renewals.show');

            Route::get('/payments', [MediaHousePortalController::class, 'payments'])->name('payments');
            Route::get('/notices', [MediaHousePortalController::class, 'notices'])->name('notices');
            Route::get('/howto', [MediaHousePortalController::class, 'howto'])->name('howto');
            Route::get('/profile', [MediaHousePortalController::class, 'profile'])->name('profile');
            Route::post('/profile', [MediaHousePortalController::class, 'updateProfile'])->name('profile.update');
            Route::get('/settings', [MediaHousePortalController::class, 'settings'])->name('settings');

            // Downloads
            Route::get('/downloads', [\App\Http\Controllers\Portal\DownloadsController::class, 'index'])
                ->defaults('portal', 'mediahouse')
                ->name('downloads');
            Route::get('/downloads/file/{doc}', [\App\Http\Controllers\Portal\DownloadsController::class, 'download'])
                ->defaults('portal', 'mediahouse')
                ->name('downloads.file');

            // Official Letter Download (Two-Stage Payment)
            Route::get('/applications/{application}/official-letter', [MediaHousePortalController::class, 'downloadOfficialLetter'])
                ->name('download-official-letter');

            // Two-Stage Payment Submissions
            Route::post('/applications/{application}/payment/application-fee/paynow', [MediaHousePortalController::class, 'submitApplicationFeePaynow'])
                ->name('payment.app-fee.paynow');
            Route::post('/applications/{application}/payment/application-fee/proof', [MediaHousePortalController::class, 'submitApplicationFeeProof'])
                ->name('payment.app-fee.proof');
            Route::post('/applications/{application}/payment/registration-fee/paynow', [MediaHousePortalController::class, 'submitRegistrationFeePaynow'])
                ->name('payment.reg-fee.paynow');
            Route::post('/applications/{application}/payment/registration-fee/proof', [MediaHousePortalController::class, 'submitRegistrationFeeProof'])
                ->name('payment.reg-fee.proof');
        });

    /*
    |--------------------------------------------------------------------------
    | MESSAGING (APPLICANTS + STAFF)
    |--------------------------------------------------------------------------
    */
    Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::get('/messages/application/{application}', [MessagesController::class, 'thread'])->name('messages.thread');
    Route::post('/messages/application/{application}', [MessagesController::class, 'send'])->name('messages.send');

    /*
    |--------------------------------------------------------------------------
    | APPLICANT JSON DETAILS (for applicant dashboard View modal)
    |--------------------------------------------------------------------------
    */
    Route::get('/portal/applications/{application}/details', [PortalApplicationDetailsController::class, 'show'])
        ->name('portal.applications.details');

    /*
    |--------------------------------------------------------------------------
    | Notices & Events (Applicant view)
    |--------------------------------------------------------------------------
    */
    Route::get('/portal/notices-events', function () {
        return redirect()->route('mediahouse.notices');
    })->name('portal.notices-events.index');

    Route::get('/portal/receipt/{payment}/download', [\App\Http\Controllers\Portal\PortalReceiptController::class, 'download'])
        ->name('portal.receipt.download');

    /*
    |--------------------------------------------------------------------------
    | PAYNOW PAYMENTS
    |--------------------------------------------------------------------------
    */
    Route::post('/payments/{applicationId}/initiate', [\App\Http\Controllers\Portal\PaynowController::class, 'initiate'])
        ->name('paynow.initiate');
    Route::get('/payments/rbz-rate', [\App\Http\Controllers\Portal\PaynowController::class, 'rbzRate'])
        ->name('paynow.rbz-rate');
    Route::post('/payments/{applicationId}/initiate-mobile', [\App\Http\Controllers\Portal\PaynowController::class, 'initiateMobile'])
        ->name('paynow.initiate.mobile');
    Route::get('/payments/{applicationId}/status', [\App\Http\Controllers\Portal\PaynowController::class, 'checkStatus'])
        ->name('paynow.status');
    Route::post('/payments/{applicationId}/submit-reference', [\App\Http\Controllers\Portal\PaynowController::class, 'submitReference'])
        ->name('paynow.submit_reference');

    /*
    |--------------------------------------------------------------------------
    | MANUAL PAYMENTS (Proof / Waiver uploads)
    |--------------------------------------------------------------------------
    */
    Route::post('/payments/{application}/upload-proof', [\App\Http\Controllers\Portal\ManualPaymentController::class, 'uploadProof'])
        ->name('payments.upload_proof');
    Route::post('/payments/{application}/upload-waiver', [\App\Http\Controllers\Portal\ManualPaymentController::class, 'uploadWaiver'])
        ->name('payments.upload_waiver');

    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN PANEL
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin|director|it_admin|registrar'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/refresh', [AdminDashboardController::class, 'refresh'])->name('dashboard.refresh');

            // Analytics
            Route::get('/analytics', [AdminAnalyticsController::class, 'index'])
                ->name('analytics');

            // Downloads / exports
            Route::get('/downloads', [\App\Http\Controllers\Admin\DownloadsController::class, 'index'])
                ->name('downloads.index');
            Route::get('/downloads/csv/{type}', [\App\Http\Controllers\Admin\DownloadsController::class, 'csv'])
                ->name('downloads.csv');

            // Read-only application lists for admin oversight
            Route::get('/media-house-registrations', [AdminApplicationsController::class, 'mediaHouse'])->name('mediahouse.index');
            Route::get('/journalist-accreditations', [AdminApplicationsController::class, 'accreditation'])->name('accreditation.index');
            Route::get('/applications/{application}', [AdminApplicationsController::class, 'show'])->name('applications.show');
            // Content: Notices & Events
            Route::get('/content', [\App\Http\Controllers\Admin\ContentController::class, 'index'])
                ->middleware('module.enabled:notices')
                ->name('content.index');
            Route::post('/content/notices', [\App\Http\Controllers\Admin\ContentController::class, 'storeNotice'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:notices'])
                ->name('content.notices.store');
            Route::put('/content/notices/{notice}', [\App\Http\Controllers\Admin\ContentController::class, 'updateNotice'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:notices'])
                ->name('content.notices.update');
            Route::delete('/content/notices/{notice}', [\App\Http\Controllers\Admin\ContentController::class, 'destroyNotice'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:notices'])
                ->name('content.notices.destroy');

            Route::post('/content/events', [\App\Http\Controllers\Admin\ContentController::class, 'storeEvent'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:events'])
                ->name('content.events.store');
            Route::put('/content/events/{event}', [\App\Http\Controllers\Admin\ContentController::class, 'updateEvent'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:events'])
                ->name('content.events.update');
            Route::delete('/content/events/{event}', [\App\Http\Controllers\Admin\ContentController::class, 'destroyEvent'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:events'])
                ->name('content.events.destroy');


            // News (for website)
            Route::get('/news', [\App\Http\Controllers\Admin\NewsController::class, 'index'])
                ->middleware('module.enabled:news')
                ->name('news.index');
            Route::post('/news', [\App\Http\Controllers\Admin\NewsController::class, 'store'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:news'])
                ->name('news.store');
            Route::put('/news/{news}', [\App\Http\Controllers\Admin\NewsController::class, 'update'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:news'])
                ->name('news.update');
            Route::delete('/news/{news}', [\App\Http\Controllers\Admin\NewsController::class, 'destroy'])
                ->middleware(['role:super_admin|it_admin', 'module.enabled:news'])
                ->name('news.destroy');

            // System governance (Super Admin)
            Route::get('/workflow', [AdminSystemController::class, 'workflow'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('workflow.index');

            Route::get('/templates', [AdminSystemController::class, 'templates'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('templates.index');

            Route::get('/fees', [AdminSystemController::class, 'fees'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('fees.index');

            Route::get('/audit', [AdminSystemController::class, 'audit'])
                ->middleware('role:super_admin|it_admin|director|registrar')
                ->name('audit.index');

            Route::get('/audit/role-assignments', [AdminSystemController::class, 'roleAssignmentsAudit'])
                ->middleware('role:super_admin|auditor')
                ->name('audit.role-assignments');

            Route::get('/audit/role-assignments/export', [AdminSystemController::class, 'exportRoleAssignments'])
                ->middleware('role:super_admin|auditor')
                ->name('audit.role-assignments.export');

            Route::get('/regions', [AdminSystemController::class, 'regions'])
                ->middleware('role:super_admin|it_admin|director|registrar')
                ->name('regions.index');

            Route::get('/system-health', [AdminSystemController::class, 'health'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('health.index');

            // Super Admin advanced console
            Route::get('/login-activity', [SuperAdminConfigController::class, 'loginActivity'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('users.login_activity');

            Route::match(['get', 'post'], '/workflow-config', [SuperAdminConfigController::class, 'workflowConfig'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('workflow.config');

            Route::match(['get', 'post'], '/fees-config', [SuperAdminConfigController::class, 'feesConfig'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('fees.config');

            Route::match(['get', 'post'], '/templates-config', [SuperAdminConfigController::class, 'templates'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('templates.config');

            Route::match(['get', 'post'], '/content-control', [SuperAdminConfigController::class, 'contentControl'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('content.control');

            Route::match(['get', 'post'], '/regions-offices', [SuperAdminConfigController::class, 'regionsOffices'])
                ->middleware('role:super_admin|it_admin|director|pr_officer')
                ->name('regions.offices');

            Route::match(['get', 'post'], '/master-settings', [SuperAdminConfigController::class, 'masterSettings'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('system.master_settings');

            // Backward compatible route (old link)
            Route::get('/system-settings', fn() => redirect()->route('admin.system.master_settings'))
                ->middleware('role:super_admin|it_admin|director')
                ->name('system.settings');

            Route::get('/reports', [SuperAdminConfigController::class, 'reports'])
                ->middleware('role:super_admin|it_admin|director')
                ->name('reports.index');

            // Settings (profile, password, theme)
            Route::get('/settings', [AdminSystemController::class, 'settings'])
                ->name('settings.index');
            Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
            Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
            Route::post('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme.update');



            // Users + access
            Route::get('/users', [UserAccessController::class, 'index'])
                ->middleware('role:super_admin|director|it_admin')
                ->name('users.index');
            // Split users into separate lists (pages)
            Route::get('/users/staff', [UserAccessController::class, 'staffIndex'])
                ->middleware('role:super_admin|director|it_admin')
                ->name('users.staff');
            Route::get('/users/public', [UserAccessController::class, 'publicIndex'])
                ->middleware('role:super_admin|director|it_admin')
                ->name('users.public');

            Route::get('/users/create', [UserAccessController::class, 'create'])
                ->middleware('role:super_admin|director|it_admin')
                ->name('users.create');
            Route::post('/users', [UserAccessController::class, 'store'])
                ->middleware('role:super_admin|director|it_admin')
                ->name('users.store');

            Route::get('/users/{user}/access', [UserAccessController::class, 'editAccess'])
                ->middleware('role:super_admin|director|it_admin')
                ->name('users.access.edit');
            Route::post('/users/{user}/access', [UserAccessController::class, 'updateAccess'])
                ->middleware('role:super_admin|director|it_admin')
                ->name('users.access.update');
            Route::post('/users/{user}/reset', [UserAccessController::class, 'resetAccount'])
                ->middleware('role:super_admin|director|it_admin')
                ->name('users.reset');

            // Roles + permissions (Super Admin & Director only)
            Route::get('/roles', [RolePermissionController::class, 'rolesIndex'])
                ->middleware('role:super_admin|director')
                ->name('roles.index');
            Route::post('/roles', [RolePermissionController::class, 'rolesStore'])
                ->middleware('role:super_admin|director')
                ->name('roles.store');

            Route::get('/roles/{role}/edit', [RolePermissionController::class, 'rolesEdit'])
                ->middleware('role:super_admin|director')
                ->name('roles.edit');
            Route::post('/roles/{role}', [RolePermissionController::class, 'rolesUpdate'])
                ->middleware('role:super_admin|director')
                ->name('roles.update');

            Route::get('/permissions', [RolePermissionController::class, 'permissionsIndex'])
                ->middleware('role:super_admin|director')
                ->name('permissions.index');
            Route::get('/permissions/matrix', [RolePermissionController::class, 'permissionMatrix'])
                ->middleware('role:super_admin|director')
                ->name('permissions.matrix');
            Route::post('/permissions', [RolePermissionController::class, 'permissionsStore'])
                ->middleware('role:super_admin|director')
                ->name('permissions.store');

            // User approvals (created/managed by IT Admin & Director/Super Admin)
            Route::get('/user-approvals', [UserApprovalController::class, 'index'])
                ->name('approvals.index');
            Route::post('/user-approvals/{user}/approve', [UserApprovalController::class, 'approve'])
                ->name('approvals.approve');
            Route::post('/user-approvals/{user}/reject', [UserApprovalController::class, 'reject'])
                ->name('approvals.reject');
            Route::delete('/users/{user}', [UserAccessController::class, 'destroy'])
                ->middleware('role:super_admin')
                ->name('users.destroy');
        });



    Route::post('/website/complaints', [\App\Http\Controllers\Website\ComplaintsController::class, 'store'])->name('website.complaints.store');

    // Account Setup (Post-Reset)
    Route::get('/account/setup/{token}', [\App\Http\Controllers\Auth\AccountSetupController::class, 'show'])->name('account.setup');
    Route::post('/account/setup/{token}', [\App\Http\Controllers\Auth\AccountSetupController::class, 'update'])->name('account.setup.update');
    /*
        |--------------------------------------------------------------------------
        | STAFF - JSON DETAILS (for dashboard view modals)
        |--------------------------------------------------------------------------
        */
    Route::middleware(['staff.portal', 'role:accreditation_officer|accounts_payments|registrar|production'])
        ->get('/staff/applications/{application}/details', [ApplicationDetailsController::class, 'show'])
        ->name('staff.applications.details');

    Route::middleware(['staff.portal'])->get('/staff/search', [ApplicationDetailsController::class, 'globalSearch'])->name('staff.search');

    // Secure staff-only document viewing (applicant uploads)
    Route::middleware(['staff.portal', 'role:accreditation_officer|registrar|accounts_payments|production|auditor|super_admin|it_admin|director'])
        ->get('/staff/documents/{document}', [\App\Http\Controllers\Staff\DocumentViewController::class, 'show'])
        ->name('staff.documents.show');

    Route::middleware(['staff.portal', 'role:accreditation_officer|registrar|accounts_payments|production|super_admin|it_admin|director'])
        ->post('/staff/applications/{application}/comment', [\App\Http\Controllers\Staff\ApplicationCommentController::class, 'store'])
        ->name('staff.applications.comment');

    /*
    |--------------------------------------------------------------------------
    | STAFF - ACCREDITATION OFFICER
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:accreditation_officer', 'block.director.operational'])
        ->prefix('staff/accreditation-officer')
        ->name('staff.officer.')
        ->group(function () {
            Route::get('/', [AccreditationOfficerController::class, 'dashboard'])->name('dashboard');
            Route::get('/applications/{application}', [AccreditationOfficerController::class, 'show'])->name('applications.show');
            Route::get('/applications/{application}/documents/zip-download', [AccreditationOfficerController::class, 'downloadApplicationDocumentsZip'])->name('applications.zip-download');
            Route::post('/applications/{application}/approve', [AccreditationOfficerController::class, 'approve'])->name('applications.approve');
            Route::post('/applications/{application}/request-correction', [AccreditationOfficerController::class, 'requestCorrection'])->name('applications.requestCorrection');
            Route::post('/applications/{application}/message', [AccreditationOfficerController::class, 'sendMessage'])->name('applications.message');
            Route::post('/applications/{application}/unlock', [AccreditationOfficerController::class, 'unlock'])->name('applications.unlock');

            // Applications (list views)
            Route::get('/applications', [AccreditationOfficerController::class, 'applicationsIndex'])->name('applications.index');
            Route::get('/applications-new', [AccreditationOfficerController::class, 'applicationsNew'])->name('applications.new');
            Route::get('/applications-new-apps', [AccreditationOfficerController::class, 'applicationsNewApps'])->name('applications.new-apps');
            Route::get('/applications-renewals', [AccreditationOfficerController::class, 'applicationsRenewals'])->name('applications.renewals');
            Route::get('/applications-replacements', [AccreditationOfficerController::class, 'applicationsReplacements'])->name('applications.replacements');
            Route::get('/applications-pending', [AccreditationOfficerController::class, 'applicationsPending'])->name('applications.pending');
            Route::get('/applications-approved', [AccreditationOfficerController::class, 'applicationsApproved'])->name('applications.approved');
            Route::get('/applications-rejected', [AccreditationOfficerController::class, 'applicationsRejected'])->name('applications.rejected');
            // drafts remain only in applicant portal
            Route::get('/applications-bulk', [AccreditationOfficerController::class, 'applicationsBulk'])->name('applications.bulk');
            Route::get('/applications-export/{list}', [AccreditationOfficerController::class, 'applicationsExportCsv'])
                ->whereIn('list', ['all', 'new', 'pending', 'approved', 'rejected'])
                ->name('applications.export');

            // Records (journalists & mediahouses moved to shared group below - accessible by registrar too)
            Route::get('/records/history', [AccreditationOfficerController::class, 'recordsHistory'])->name('records.history');
            // suspended/revoked page removed
            Route::get('/records/renewals', [AccreditationOfficerController::class, 'recordsRenewals'])->name('records.renewals');

            // Document verification
            Route::get('/documents/uploaded', [AccreditationOfficerController::class, 'documentsUploaded'])->name('documents.uploaded');
            Route::get('/documents/pending', [AccreditationOfficerController::class, 'documentsPending'])->name('documents.pending');
            Route::get('/documents/verified', [AccreditationOfficerController::class, 'documentsVerified'])->name('documents.verified');
            Route::get('/documents/rejected', [AccreditationOfficerController::class, 'documentsRejected'])->name('documents.rejected');

            // Renewals & expiry
            Route::get('/renewals/expiring', [AccreditationOfficerController::class, 'renewalsExpiring'])->name('renewals.expiring');
            Route::get('/renewals/expired', [AccreditationOfficerController::class, 'renewalsExpired'])->name('renewals.expired');
            Route::get('/renewals/requests', [AccreditationOfficerController::class, 'renewalsRequests'])->name('renewals.requests');
            Route::get('/renewals/queue', [AccreditationOfficerController::class, 'renewalsQueue'])->name('renewals.queue');
            Route::post('/renewals/send-reminders', [AccreditationOfficerController::class, 'sendRenewalReminders'])->name('renewals.send-reminders');

            // Physical Intake (walk-in)
            Route::get('/physical-intake', [AccreditationOfficerController::class, 'physicalIntakeForm'])->name('physical-intake');
            Route::post('/physical-intake', [AccreditationOfficerController::class, 'physicalIntakeProcess'])->name('physical-intake.process');


            // Records management (accredited/registered views moved to shared group below)


            // Compliance
            Route::get('/compliance/monitoring', [AccreditationOfficerController::class, 'complianceMonitoring'])->name('compliance.monitoring');
            Route::get('/compliance/violations', [AccreditationOfficerController::class, 'complianceViolations'])->name('compliance.violations');
            Route::get('/compliance/cases', [AccreditationOfficerController::class, 'complianceCases'])->name('compliance.cases');
            Route::get('/compliance/unaccredited', [AccreditationOfficerController::class, 'complianceUnaccredited'])->name('compliance.unaccredited');

            // Reports
            Route::get('/reports/stats', [AccreditationOfficerController::class, 'reportsStats'])->name('reports.stats');
            Route::get('/reports/monthly', [AccreditationOfficerController::class, 'reportsMonthly'])->name('reports.monthly');
            Route::get('/reports/trends', [AccreditationOfficerController::class, 'reportsTrends'])->name('reports.trends');
            Route::get('/reports/compliance', [AccreditationOfficerController::class, 'reportsCompliance'])->name('reports.compliance');

            // Notices & communication
            Route::get('/comm/notices', [AccreditationOfficerController::class, 'commNotices'])->name('comm.notices');
            Route::get('/comm/announcements', [AccreditationOfficerController::class, 'commAnnouncements'])->name('comm.announcements');
            Route::get('/comm/memos', [AccreditationOfficerController::class, 'commMemos'])->name('comm.memos');
            Route::get('/comm/messaging', [AccreditationOfficerController::class, 'commMessaging'])->name('comm.messaging');

            // Advanced tools
            Route::get('/advanced/duplicates', [AccreditationOfficerController::class, 'advancedDuplicates'])->name('advanced.duplicates');
            Route::get('/advanced/forgery', [AccreditationOfficerController::class, 'advancedForgery'])->name('advanced.forgery');
            Route::get('/advanced/qr', [AccreditationOfficerController::class, 'advancedQr'])->name('advanced.qr');
            Route::get('/advanced/audit', [AccreditationOfficerController::class, 'advancedAudit'])->name('advanced.audit');

            // Profile & tools
            Route::get('/tools/profile', [AccreditationOfficerController::class, 'toolsProfile'])->name('tools.profile');
            Route::get('/tools/tasks', [AccreditationOfficerController::class, 'toolsTasks'])->name('tools.tasks');
            Route::get('/tools/drafts', [AccreditationOfficerController::class, 'toolsDrafts'])->name('tools.drafts');
            Route::get('/tools/sops', [AccreditationOfficerController::class, 'toolsSops'])->name('tools.sops');

            // Fix Requests
            Route::get('/fix-requests', [AccreditationOfficerController::class, 'fixRequests'])->name('fix-requests');
            Route::post('/fix-requests/{fixRequest}/resolve', [AccreditationOfficerController::class, 'resolveFixRequest'])->name('fix-requests.resolve');

            // Forward Without Approval (Waiver/Special Cases)
            Route::post('/applications/{application}/forward-no-approval', [AccreditationOfficerController::class, 'forwardWithoutApproval'])->name('applications.forward-no-approval');

            // Processed Applications (Cards/Certificates in production/printed/issued)
            Route::get('/processed-applications', [AccreditationOfficerController::class, 'processedApplications'])->name('processed');

            // Renewals Production
            Route::get('/renewals-production', [AccreditationOfficerController::class, 'renewalsProductionQueue'])->name('renewals.production');
            Route::get('/renewals-production/{renewal}', [AccreditationOfficerController::class, 'showRenewalProduction'])->name('renewals.production.show');
            Route::post('/renewals-production/{renewal}/generate', [AccreditationOfficerController::class, 'generateRenewalDocument'])->name('renewals.production.generate');
            Route::post('/renewals-production/{renewal}/mark-produced', [AccreditationOfficerController::class, 'markRenewalProduced'])->name('renewals.production.mark-produced');
            Route::post('/renewals-production/{renewal}/print', [AccreditationOfficerController::class, 'printRenewalDocument'])->name('renewals.production.print');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - SHARED RECORDS (Accreditation Officer + Registrar)
    | Read-only record views accessible by both roles for oversight.
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:accreditation_officer|registrar'])
        ->prefix('staff/accreditation-officer')
        ->name('staff.officer.')
        ->group(function () {
            // Shared read-only records: accessible by both Accreditation Officers and Registrars.
            Route::get('/records/journalists', [AccreditationOfficerController::class, 'recordsJournalists'])
                ->name('records.journalists');
            Route::get('/records/mediahouses', [AccreditationOfficerController::class, 'recordsMediaHouses'])
                ->name('records.mediahouses');
            // Actual destination pages (the above methods redirect here)
            Route::get('/records/accredited-journalists', [AccreditationOfficerController::class, 'accreditedJournalists'])
                ->name('records.accredited-journalists');
            Route::get('/records/registered-mediahouses', [AccreditationOfficerController::class, 'registeredMediaHouses'])
                ->name('records.registered-mediahouses');
            Route::post('/records/mediahouses/{record}/update', [AccreditationOfficerController::class, 'updateMediaHouseRecord'])
                ->name('records.mediahouses.update');
            Route::post('/records/publications/{publication}/update', [AccreditationOfficerController::class, 'updatePublicationRecord'])
                ->name('records.publications.update');
            Route::post('/records/journalists/{record}/update', [AccreditationOfficerController::class, 'updateJournalistRecord'])
                ->name('records.journalists.update');
            // Export & collection notification — shared so Registrar can also export
            Route::get('/records/export/{type}', [AccreditationOfficerController::class, 'recordsExport'])
                ->whereIn('type', ['journalists', 'mediahouses'])
                ->name('records.export');
            Route::post('/records/send-collection-notification', [AccreditationOfficerController::class, 'sendCollectionNotification'])
                ->name('records.send-collection-notification');
        });


    /*
    |--------------------------------------------------------------------------
    | STAFF - REGISTRAR
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:registrar', 'block.director.operational'])
        ->prefix('staff/registrar')
        ->name('staff.registrar.')
        ->group(function () {
            Route::get('/', [RegistrarController::class, 'dashboard'])->name('dashboard');
            // Keep the optional bucket route from swallowing /applications/{id}
            // links. Review links must resolve to the application detail page.
            Route::get('/applications/{bucket?}', [RegistrarController::class, 'applicationsList'])
                ->whereIn('bucket', ['all', 'under-review', 'approved', 'returned', 'pending'])
                ->name('applications.list');
            Route::get('/approved-applications', [RegistrarController::class, 'approvedApplications'])->name('approved-applications');
            Route::get('/documents/uploaded', [RegistrarController::class, 'documentsUploaded'])->name('documents.uploaded');
            Route::get('/applications/{application}/documents/zip-download', [RegistrarController::class, 'downloadDocumentsZip'])->name('applications.zip-download');
            Route::get('/applications/{application}/download-all-documents', [RegistrarController::class, 'downloadAllDocuments'])->name('applications.download-all-documents');
            Route::get('/reports', [RegistrarController::class, 'reports'])->name('reports');
            Route::get('/reports/pdf', [RegistrarController::class, 'reportsPdf'])->name('reports.pdf');
            Route::get('/audit-trail', [RegistrarController::class, 'auditTrailSearch'])->name('audit-trail');
            Route::post('/applications/{application}/reassign-category', [RegistrarController::class, 'reassignCategory'])->name('applications.reassign-category');
            Route::post('/applications/{application}/approve-for-payment', [RegistrarController::class, 'approveForPayment'])->name('applications.approve-for-payment');


            Route::get('/applications/{application}', [RegistrarController::class, 'show'])->name('applications.show');
            Route::post('/applications/{application}/approve', [RegistrarController::class, 'approve'])->name('applications.approve');
            Route::post('/applications/{application}/mark-reviewed', [RegistrarController::class, 'markReviewed'])->name('applications.mark-reviewed');
            Route::post('/applications/batch-mark-reviewed', [RegistrarController::class, 'batchMarkReviewed'])->name('applications.batch-mark-reviewed');
            Route::post('/applications/{application}/reject', [RegistrarController::class, 'reject'])->name('applications.reject');
            Route::post('/applications/{application}/return', [RegistrarController::class, 'returnToOfficer'])->name('applications.return');
            Route::post('/renewals/send-reminders', [RegistrarController::class, 'sendRenewalReminders'])->name('renewals.send-reminders');

            // Fix Requests
            Route::get('/fix-requests', [RegistrarController::class, 'fixRequests'])->name('fix-requests');
            Route::post('/applications/{application}/send-fix-request', [RegistrarController::class, 'sendFixRequest'])->name('applications.send-fix-request');

            // Special Cases (Forwarded Without Approval)
            Route::post('/applications/{application}/approve-special-case', [RegistrarController::class, 'approveSpecialCase'])->name('applications.approve-special-case');

            // Media House Two-Stage Payment: Official Letter Upload
            Route::post('/applications/{application}/approve-with-letter', [RegistrarController::class, 'approveWithOfficialLetter'])->name('applications.approve-with-letter');

            // Reminders
            Route::get('/reminders', [RegistrarController::class, 'remindersIndex'])->name('reminders.index');
            Route::post('/reminders', [RegistrarController::class, 'storeReminder'])->name('reminders.store');

            // Payment Oversight (Read-Only)
            Route::get('/payment-oversight', [RegistrarController::class, 'paymentOversight'])->name('payment-oversight');
            Route::get('/payment-oversight/{paymentSubmission}', [RegistrarController::class, 'paymentDetail'])->name('payment-detail');

            // Processed Applications (Cards/Certificates in production/printed/issued)
            Route::get('/processed-applications', [RegistrarController::class, 'processedApplications'])->name('processed');

            // Notices & Events (Read-only access)
            Route::get('/notices-events', [RegistrarController::class, 'noticesEvents'])->name('notices-events');

            // News / Press Statements (Read-only access)
            Route::get('/news', [RegistrarController::class, 'news'])->name('news');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - ACCOUNTS/PAYMENTS
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:accounts_payments', 'block.director.operational'])
        ->prefix('staff/accounts')
        ->name('staff.accounts.')
        ->group(function () {
            Route::get('/rbz-rate', [AccountsPaymentsController::class, 'rbzRate'])->name('rbz-rate');
            Route::post('/rbz-rate', [AccountsPaymentsController::class, 'updateRbzRate'])->name('rbz-rate.update');
            Route::get('/', [AccountsPaymentsController::class, 'dashboard'])->name('dashboard');

            // --- ACCOUNTS DASHBOARD - New Core Modules ---
            Route::get('/payments', [AccountsPaymentsController::class, 'index'])->name('payments.index');
            Route::get('/payments/retry/{payment}', [AccountsPaymentsController::class, 'retryPaymentStatus'])->name('payments.retry');
            Route::get('/payments/offline/create', [AccountsPaymentsController::class, 'createOffline'])->name('payments.offline.create');
            Route::post('/payments/offline/store', [AccountsPaymentsController::class, 'storeOffline'])->name('payments.offline.store');
            Route::post('/payments/{payment}/reverse', [AccountsPaymentsController::class, 'reverse'])->name('payments.reverse');
            Route::post('/payments/{payment}/refund', [AccountsPaymentsController::class, 'initiateRefund'])->name('payments.refund');
            Route::post('/refunds/{refund}/approve', [AccountsPaymentsController::class, 'approveRefund'])->name('refunds.approve');
            Route::get('/ledger', [AccountsPaymentsController::class, 'ledger'])->name('ledger');
            Route::post('/reconciliation/mark', [AccountsPaymentsController::class, 'markReconciled'])->name('reconciliation.mark');
            Route::get('/reports/financial', [AccountsPaymentsController::class, 'reportFinancial'])->name('reports.financial');
            // ----------------------------------------------

            // 1) Payments dashboard (PayNow feed)
            Route::get('/paynow-transactions', [AccountsPaymentsController::class, 'paynowTransactions'])->name('paynow.transactions');

            // 2) Payment proofs
            Route::get('/payment-proofs/pending', [AccountsPaymentsController::class, 'proofsPending'])->name('proofs.pending');
            Route::get('/app-fee-verifications', [AccountsPaymentsController::class, 'appFeeVerifications'])->name('app-fee-verifications');
            Route::get('/reg-fee-verifications', [AccountsPaymentsController::class, 'regFeeVerifications'])->name('reg-fee-verifications');
            Route::get('/payment-proofs/approved', [AccountsPaymentsController::class, 'proofsApproved'])->name('proofs.approved');
            Route::post('/payment-proofs/bulk-download', [AccountsPaymentsController::class, 'bulkDownloadProofs'])->name('proofs.bulk-download');
            Route::post('/applications/{application}/proof/approve', [AccountsPaymentsController::class, 'approveProof'])->name('proofs.approve');
            Route::post('/applications/{application}/proof/reject', [AccountsPaymentsController::class, 'rejectProof'])->name('proofs.reject');

            // Receipting
            Route::get('/applications/{application}/receipt', [AccountsPaymentsController::class, 'generateReceipt'])->name('applications.receipt');

            // 3) Waivers
            Route::get('/waivers/requests', [AccountsPaymentsController::class, 'waiversRequests'])->name('waivers.requests');
            Route::get('/waivers/approved', [AccountsPaymentsController::class, 'waiversApproved'])->name('waivers.approved');
            Route::get('/waivers/rejected', [AccountsPaymentsController::class, 'waiversRejected'])->name('waivers.rejected');
            Route::post('/applications/{application}/waiver/approve', [AccountsPaymentsController::class, 'approveWaiver'])->name('waivers.approve');
            Route::post('/applications/{application}/waiver/reject', [AccountsPaymentsController::class, 'rejectWaiver'])->name('waivers.reject');

            // 4) Reconciliation
            Route::get('/reconciliation', [AccountsPaymentsController::class, 'reconciliation'])->name('reconciliation');

            // 5) Application status tracking
            Route::get('/applications/paid', [AccountsPaymentsController::class, 'applicationsPaid'])->name('apps.paid');
            Route::get('/applications/pending', [AccountsPaymentsController::class, 'applicationsPending'])->name('apps.pending');
            Route::get('/applications/waived', [AccountsPaymentsController::class, 'applicationsWaived'])->name('apps.waived');

            // 6) Reports
            Route::get('/reports/revenue', [AccountsPaymentsController::class, 'reportRevenue'])->name('reports.revenue');
            Route::get('/reports/export-ledger', [AccountsPaymentsController::class, 'exportLedger'])->name('reports.export-ledger');
            Route::get('/reports/exceptions', [AccountsPaymentsController::class, 'reportExceptions'])->name('reports.exceptions');
            Route::get('/reports/audit', [AccountsPaymentsController::class, 'reportAudit'])->name('reports.audit');

            // 6) Notifications & alerts
            Route::get('/alerts', [AccountsPaymentsController::class, 'alerts'])->name('alerts');

            // 7) System tools
            Route::get('/tools/paynow-settings', [AccountsPaymentsController::class, 'paynowSettings'])->name('tools.paynow');
            Route::get('/tools/user-action-logs', [AccountsPaymentsController::class, 'userActionLogs'])->name('tools.logs');

            // Processed Applications (Cards/Certificates in production/printed/issued)
            Route::get('/processed-applications', [AccountsPaymentsController::class, 'processedApplications'])->name('processed');

            // Help
            Route::get('/help', [AccountsPaymentsController::class, 'help'])->name('help');

            Route::get('/applications/{application}', [AccountsPaymentsController::class, 'show'])->name('applications.show');
            Route::post('/applications/{application}/paid', [AccountsPaymentsController::class, 'markPaid'])->name('applications.paid');
            Route::get('/applications/{application}/paid', fn($application) => redirect()->route('staff.accounts.applications.show', $application));
            Route::post('/applications/{application}/return', [AccountsPaymentsController::class, 'returnToOfficer'])->name('applications.return');
            Route::post('/applications/{application}/unlock', [AccountsPaymentsController::class, 'unlock'])->name('applications.unlock');

            // Payment Submission Verification (Waivers & Special Cases)
            Route::post('/applications/{application}/verify-payment', [AccountsPaymentsController::class, 'verifyPaymentSubmission'])->name('applications.verify-payment');

            // Renewals Queue
            Route::get('/renewals', [AccountsPaymentsController::class, 'renewalsQueue'])->name('renewals.queue');
            Route::get('/renewals/{renewal}', [AccountsPaymentsController::class, 'showRenewal'])->name('renewals.show');
            Route::post('/renewals/{renewal}/verify', [AccountsPaymentsController::class, 'verifyRenewalPayment'])->name('renewals.verify');

            // Levy Payment Verification
            Route::get('/levies/pending', [AccountsPaymentsController::class, 'leviesPending'])->name('levies.pending');
            Route::get('/levies/verified', [AccountsPaymentsController::class, 'leviesVerified'])->name('levies.verified');
            Route::get('/levies/rejected', [AccountsPaymentsController::class, 'leviesRejected'])->name('levies.rejected');
            Route::post('/levies/{levy}/verify', [AccountsPaymentsController::class, 'verifyLevy'])->name('levies.verify');
            Route::post('/levies/{levy}/reject', [AccountsPaymentsController::class, 'rejectLevy'])->name('levies.reject');
            Route::get('/levies/documents/{document}/download', [AccountsPaymentsController::class, 'downloadLevyDocument'])->name('levies.documents.download');
            Route::post('/levies/bulk-download-proofs', [AccountsPaymentsController::class, 'bulkDownloadLevyProofs'])->name('levies.bulk-download');
            Route::get('/levies/{levy}/receipt', [AccountsPaymentsController::class, 'generateLevyReceipt'])->name('levies.receipt');

            // FOIA Appeals Payment Verification
            Route::get('/foia-appeals/pending', [AccountsPaymentsController::class, 'foiaAppealsPending'])->name('foia_appeals.pending');
            Route::post('/foia-appeals/{appeal}/approve-payment', [AccountsPaymentsController::class, 'approveFoiaAppealPayment'])->name('foia_appeals.approve_payment');
            Route::post('/foia-appeals/{appeal}/reject-payment', [AccountsPaymentsController::class, 'rejectFoiaAppealPayment'])->name('foia_appeals.reject_payment');
            Route::get('/foia-appeals/{appeal}/payment-proof', [AccountsPaymentsController::class, 'downloadFoiaAppealPaymentProof'])->name('foia_appeals.payment_proof');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - PRODUCTION
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:production|accreditation_officer', 'block.director.operational'])
        ->prefix('staff/production')
        ->name('staff.production.')
        ->group(function () {
            Route::get('/', [ProductionController::class, 'dashboard'])->name('dashboard');

            // Menu pages
            Route::get('/queue', [ProductionController::class, 'queue'])->name('queue');
            Route::get('/cards', [ProductionController::class, 'cards'])->name('cards');
            Route::get('/certificates', [ProductionController::class, 'certificates'])->name('certificates');
            Route::get('/printing', [ProductionController::class, 'printing'])->name('printing');
            Route::get('/issuance', [ProductionController::class, 'issuance'])->name('issuance');
            Route::get('/registers/issued', [ProductionController::class, 'issuedRegister'])->name('registers.issued');
            Route::get('/reports', [ProductionController::class, 'reports'])->name('reports');

            Route::get('/applications/{application}', [ProductionController::class, 'show'])->name('applications.show');
            Route::post('/applications/{application}/approve', [ProductionController::class, 'approveForGeneration'])->name('applications.approve');

            // Preview/edit before generating & printing
            Route::get('/applications/{application}/card/preview', [ProductionController::class, 'cardPreview'])->name('applications.card.preview');
            Route::post('/applications/{application}/card/print', [ProductionController::class, 'cardPrint'])->name('applications.card.print');
            Route::post('/applications/{application}/card/print-back', [ProductionController::class, 'cardPrintBack'])->name('applications.card.print_back');
            Route::post('/applications/{application}/card/print-duplex', [ProductionController::class, 'cardPrintDuplex'])->name('applications.card.print_duplex');

            Route::get('/applications/{application}/certificate/preview', [ProductionController::class, 'certificatePreview'])->name('applications.certificate.preview');
            Route::post('/applications/{application}/certificate/print', [ProductionController::class, 'certificatePrint'])->name('applications.certificate.print');

            Route::post('/applications/{application}/generate-card', [ProductionController::class, 'generateCard'])->name('applications.generate_card');
            Route::post('/applications/{application}/generate-certificate', [ProductionController::class, 'generateCertificate'])->name('applications.generate_certificate');
            Route::post('/applications/{application}/print', [ProductionController::class, 'printSingle'])->name('applications.print_single');
            Route::post('/batch/print', [ProductionController::class, 'printBatch'])->name('batch.print');
            Route::post('/applications/{application}/issue', [ProductionController::class, 'markIssued'])->name('applications.issue');
            Route::post('/applications/{application}/unlock', [ProductionController::class, 'unlock'])->name('applications.unlock');

            Route::get('/templates', [ProductionController::class, 'templates'])->name('templates');
            Route::get('/designer', [ProductionController::class, 'designer'])->name('designer');
            Route::post('/templates', [ProductionController::class, 'storeTemplate'])->name('templates.store');
            Route::put('/templates/{template}', [ProductionController::class, 'updateTemplate'])->name('templates.update');
            Route::post('/templates/{template}/activate', [ProductionController::class, 'activateTemplate'])->name('templates.activate');
        });

    /*
    |--------------------------------------------------------------------------
    | DESIGN STUDIO — Designers (Registrar, Accreditation Officer, IT, Super Admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:registrar|accreditation_officer|production|it_admin|super_admin'])
        ->prefix('staff/design-studio')
        ->name('staff.design-studio.')
        ->group(function () {
            Route::get('/',                                          [\App\Http\Controllers\Staff\DesignStudioController::class, 'dashboard'])->name('dashboard');
            Route::get('/templates',                                 [\App\Http\Controllers\Staff\DesignStudioController::class, 'index'])->name('index');
            Route::get('/templates/create',                          [\App\Http\Controllers\Staff\DesignStudioController::class, 'create'])->name('create');
            Route::post('/templates',                                [\App\Http\Controllers\Staff\DesignStudioController::class, 'store'])->name('store');
            Route::get('/templates/{designStudioTemplate}',          [\App\Http\Controllers\Staff\DesignStudioController::class, 'show'])->name('show');
            Route::get('/templates/{designStudioTemplate}/edit',     [\App\Http\Controllers\Staff\DesignStudioController::class, 'edit'])->name('edit');
            Route::put('/templates/{designStudioTemplate}',          [\App\Http\Controllers\Staff\DesignStudioController::class, 'update'])->name('update');
            Route::delete('/templates/{designStudioTemplate}',       [\App\Http\Controllers\Staff\DesignStudioController::class, 'destroy'])->name('destroy');
            Route::post('/templates/{designStudioTemplate}/submit',  [\App\Http\Controllers\Staff\DesignStudioController::class, 'submit'])->name('submit');
            Route::post('/templates/{designStudioTemplate}/duplicate',[\App\Http\Controllers\Staff\DesignStudioController::class, 'duplicate'])->name('duplicate');
            Route::get('/audit-logs',                                [\App\Http\Controllers\Staff\DesignStudioController::class, 'auditLogs'])->name('audit-logs');
        });

    /*
    |--------------------------------------------------------------------------
    | DESIGN STUDIO — Super Admin Approval
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:super_admin'])
        ->prefix('staff/design-studio/approval')
        ->name('staff.design-studio.approval.')
        ->group(function () {
            Route::get('/queue',                                              [\App\Http\Controllers\Staff\DesignStudioApprovalController::class, 'queue'])->name('queue');
            Route::post('/{designStudioTemplate}/approve',                   [\App\Http\Controllers\Staff\DesignStudioApprovalController::class, 'approve'])->name('approve');
            Route::post('/{designStudioTemplate}/reject',                    [\App\Http\Controllers\Staff\DesignStudioApprovalController::class, 'reject'])->name('reject');
            Route::post('/{designStudioTemplate}/archive',                   [\App\Http\Controllers\Staff\DesignStudioApprovalController::class, 'archive'])->name('archive');
            Route::post('/{designStudioTemplate}/restore',                   [\App\Http\Controllers\Staff\DesignStudioApprovalController::class, 'restore'])->name('restore');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - PR OFFICER (Notices & Events Management)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:pr_officer|super_admin'])
        ->prefix('staff/pr')
        ->name('staff.pr.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Staff\PrOfficerController::class, 'dashboard'])->name('dashboard');
            Route::get('/website-people', [\App\Http\Controllers\Staff\WebsitePeopleController::class, 'index'])->name('website-people.index');
            Route::post('/website-people', [\App\Http\Controllers\Staff\WebsitePeopleController::class, 'store'])->name('website-people.store');
            Route::put('/website-people/{person}', [\App\Http\Controllers\Staff\WebsitePeopleController::class, 'update'])->name('website-people.update');
            Route::delete('/website-people/{person}', [\App\Http\Controllers\Staff\WebsitePeopleController::class, 'destroy'])->name('website-people.destroy');

            // Notices Management
            Route::post('/notices', [\App\Http\Controllers\Staff\PrOfficerController::class, 'storeNotice'])->name('notices.store');
            Route::put('/notices/{notice}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'updateNotice'])->name('notices.update');
            Route::delete('/notices/{notice}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'destroyNotice'])->name('notices.destroy');

            // Events Management
            Route::post('/events', [\App\Http\Controllers\Staff\PrOfficerController::class, 'storeEvent'])->name('events.store');
            Route::put('/events/{event}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'updateEvent'])->name('events.update');
            Route::delete('/events/{event}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'destroyEvent'])->name('events.destroy');

            // News Management
            Route::post('/news', [\App\Http\Controllers\Staff\PrOfficerController::class, 'storeNews'])->name('news.store');
            Route::put('/news/{news}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'updateNews'])->name('news.update');
            Route::delete('/news/{news}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'destroyNews'])->name('news.destroy');

            // Website Contact messages
            Route::get('/messages', [\App\Http\Controllers\Staff\WebsiteMessagesController::class, 'index'])->name('messages.index');
            Route::get('/messages/export', [\App\Http\Controllers\Staff\WebsiteMessagesController::class, 'export'])->name('messages.export');
            Route::get('/messages/{message}', [\App\Http\Controllers\Staff\WebsiteMessagesController::class, 'show'])->name('messages.show');
            Route::patch('/messages/{message}', [\App\Http\Controllers\Staff\WebsiteMessagesController::class, 'update'])->name('messages.update');
            Route::patch('/messages/{message}/notes', [\App\Http\Controllers\Staff\WebsiteMessagesController::class, 'note'])->name('messages.notes');
            Route::post('/messages/{message}/reply', [\App\Http\Controllers\Staff\WebsiteMessagesController::class, 'reply'])->name('messages.reply');

            Route::post('/tenders', [\App\Http\Controllers\Staff\PrOfficerController::class, 'storeTender'])->name('tenders.store');
            Route::put('/tenders/{tender}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'updateTender'])->name('tenders.update');
            Route::delete('/tenders/{tender}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'destroyTender'])->name('tenders.destroy');

            Route::post('/vacancies', [\App\Http\Controllers\Staff\PrOfficerController::class, 'storeVacancy'])->name('vacancies.store');
            Route::put('/vacancies/{vacancy}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'updateVacancy'])->name('vacancies.update');
            Route::delete('/vacancies/{vacancy}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'destroyVacancy'])->name('vacancies.destroy');

            // Downloads / Documents & Reports Management
            Route::post('/downloads', [\App\Http\Controllers\Staff\PrOfficerController::class, 'storeDownload'])->name('downloads.store');
            Route::put('/downloads/{download}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'updateDownload'])->name('downloads.update');
            Route::delete('/downloads/{download}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'destroyDownload'])->name('downloads.destroy');

            // Subscribers Management
            Route::get('/subscribers', [\App\Http\Controllers\Staff\PrOfficerController::class, 'subscribers'])->name('subscribers');
            Route::get('/subscribers/export-csv', [\App\Http\Controllers\Staff\PrOfficerController::class, 'exportSubscribersCSV'])->name('subscribers.export.csv');
            Route::get('/subscribers/export-excel', [\App\Http\Controllers\Staff\PrOfficerController::class, 'exportSubscribersExcel'])->name('subscribers.export.excel');
            Route::get('/subscribers/export-pdf', [\App\Http\Controllers\Staff\PrOfficerController::class, 'exportSubscribersPDF'])->name('subscribers.export.pdf');
            Route::delete('/subscribers/{subscriber}', [\App\Http\Controllers\Staff\PrOfficerController::class, 'destroySubscriber'])->name('subscribers.destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - APPEALS MANAGEMENT MODULE (public_info_compliance role)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:public_info_compliance|super_admin|director'])
        ->prefix('staff/appeals')
        ->name('staff.appeals.')
        ->group(function () {
            // Dashboard
            Route::get('/', [\App\Http\Controllers\Staff\Appeals\DashboardController::class, 'index'])->name('dashboard');
            
            // Appeals List & Details
            Route::get('/list', [\App\Http\Controllers\Staff\Appeals\AppealController::class, 'index'])->name('index');
            Route::get('/{appeal}', [\App\Http\Controllers\Staff\Appeals\AppealController::class, 'show'])->name('show');
            Route::patch('/{appeal}/status', [\App\Http\Controllers\Staff\Appeals\AppealController::class, 'updateStatus'])->name('update_status');
            Route::patch('/{appeal}/assign', [\App\Http\Controllers\Staff\Appeals\AppealController::class, 'assignOfficer'])->name('assign');
            Route::post('/{appeal}/notes', [\App\Http\Controllers\Staff\Appeals\AppealController::class, 'addNote'])->name('add_note');
            
            // Communications
            Route::post('/{appeal}/communicate', [\App\Http\Controllers\Staff\Appeals\CommunicationController::class, 'store'])->name('communicate');
            
            // Payments
            Route::get('/module/payments', [\App\Http\Controllers\Staff\Appeals\PaymentController::class, 'index'])->name('payments');
            
            // Reports
            Route::get('/module/reports', [\App\Http\Controllers\Staff\Appeals\ReportController::class, 'index'])->name('reports');
            
            // Settings (Categories)
            Route::get('/module/settings', [\App\Http\Controllers\Staff\Appeals\SettingController::class, 'index'])->name('settings');
            Route::post('/module/settings/category', [\App\Http\Controllers\Staff\Appeals\SettingController::class, 'storeCategory'])->name('settings.category.store');
            Route::put('/module/settings/category/{category}', [\App\Http\Controllers\Staff\Appeals\SettingController::class, 'updateCategory'])->name('settings.category.update');
            Route::delete('/module/settings/category/{category}', [\App\Http\Controllers\Staff\Appeals\SettingController::class, 'destroyCategory'])->name('settings.category.destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - PUBLIC INFORMATION COMPLIANCE MANAGER (Complaints & Appeals)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:public_info_compliance|super_admin|director|auditor'])
        ->prefix('staff/compliance')
        ->name('staff.public_info_compliance.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Staff\PublicInfoComplianceController::class, 'dashboard'])->name('dashboard');

            // Complaints & Appeals Management
            Route::post('/complaints', [\App\Http\Controllers\Staff\PublicInfoComplianceController::class, 'store'])->name('complaints.store');
            Route::put('/complaints/{complaint}', [\App\Http\Controllers\Staff\PublicInfoComplianceController::class, 'update'])->name('complaints.update');
            Route::delete('/complaints/{complaint}', [\App\Http\Controllers\Staff\PublicInfoComplianceController::class, 'destroy'])->name('complaints.destroy');
        });

    Route::middleware(['staff.portal', 'role:registration_compliance_officer'])
        ->prefix('staff/registration-compliance')
        ->name('staff.registration-compliance.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'dashboard'])
                ->name('dashboard');
            Route::get('/analytics', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'analytics'])
                ->name('analytics');
            Route::get('/registers/media-houses', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'registers'])
                ->defaults('type', 'media_houses')->name('registers.media-houses');
            Route::get('/registers/local-practitioners', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'registers'])
                ->defaults('type', 'local_practitioners')->name('registers.local-practitioners');
            Route::get('/registers/foreign-practitioners', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'registers'])
                ->defaults('type', 'foreign_practitioners')->name('registers.foreign-practitioners');
            Route::get('/compliance/renewals-expiries', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'complianceList'])
                ->defaults('type', 'expiring')->name('compliance.renewals-expiries');
            Route::get('/compliance/reminders-notices', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'complianceList'])
                ->defaults('type', 'reminders')->name('compliance.reminders-notices');
            Route::get('/compliance/non-compliant', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'complianceList'])
                ->defaults('type', 'non_compliant')->name('compliance.non-compliant');
            Route::get('/compliance/compliant', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'complianceList'])
                ->defaults('type', 'compliant')->name('compliance.compliant');
            Route::get('/compliance/inspections', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'complianceList'])
                ->defaults('type', 'inspections')->name('compliance.inspections');
            Route::get('/compliance/annual-levies', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'levies'])
                ->name('compliance.annual-levies');
            Route::get('/settings', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'settings'])
                ->name('settings');
            Route::post('/notifications/bulk', [\App\Http\Controllers\Staff\RegistrationComplianceController::class, 'sendBulkNotification'])
                ->name('notifications.bulk');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - RESEARCH, TRAINING & DEVELOPMENT (Complaints handler)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:complaints_officer|research_training|super_admin|director|auditor'])
        ->prefix('staff/research')
        ->name('staff.research.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'dashboard'])->name('dashboard');
            Route::get('/register', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'register'])->name('register');
            Route::get('/reports', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'reports'])->name('reports');
            Route::get('/export', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'export'])->name('export');
            Route::get('/downloads', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'downloads'])->name('downloads');
            Route::get('/downloads/{complaint}/{communication}/{index}', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'downloadAttachment'])->name('download.communication');
            Route::get('/downloads/{complaint}', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'downloadAttachment'])->name('download');
            Route::get('/settings', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'settings'])->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'saveSettings'])->name('settings.save');
            Route::patch('/complaints/{complaint}', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'update'])->name('update');
            Route::post('/complaints/{complaint}/reply', [\App\Http\Controllers\Staff\ResearchTrainingController::class, 'reply'])->name('reply');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - SUPERADMIN APPLICATION REVIEW
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:super_admin'])
        ->prefix('staff/superadmin/applications')
        ->name('staff.superadmin.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Staff\SuperAdminDashboardController::class, 'applications'])->name('applications.index');
            Route::get('/{application}', [\App\Http\Controllers\Staff\SuperAdminDashboardController::class, 'showApplication'])->name('applications.show');
            Route::post('/{application}/approve', [\App\Http\Controllers\Staff\SuperAdminDashboardController::class, 'approveApplication'])->name('applications.approve');
            Route::post('/{application}/reject', [\App\Http\Controllers\Staff\SuperAdminDashboardController::class, 'rejectApplication'])->name('applications.reject');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - IT ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:it_admin|super_admin'])
        ->prefix('staff/it')
        ->name('staff.it.')
        ->group(function () {
            Route::get('/', [ItDashboardController::class, 'index'])->name('dashboard');
            Route::get('/users/create', [ItAdminController::class, 'createUser'])->name('users.create');
            Route::post('/users', [ItAdminController::class, 'storeUser'])->name('users.store');

            // Regions
            Route::post('/regions', [ItAdminController::class, 'storeRegion'])->name('regions.store');
            Route::post('/regions/{region}/toggle', [ItAdminController::class, 'toggleRegion'])->name('regions.toggle');

            // Content Management (Notices, Events, Vacancies, Tenders)
            Route::post('/notices', [ContentController::class, 'storeNotice'])->name('notices.store');
            Route::post('/notices/{notice}', [ContentController::class, 'updateNotice'])->name('notices.update');
            Route::delete('/notices/{notice}', [ContentController::class, 'destroyNotice'])->name('notices.destroy');

            Route::post('/events', [ContentController::class, 'storeEvent'])->name('events.store');
            Route::post('/events/{event}', [ContentController::class, 'updateEvent'])->name('events.update');
            Route::delete('/events/{event}', [ContentController::class, 'destroyEvent'])->name('events.destroy');

            Route::post('/vacancies', [ContentController::class, 'storeVacancy'])->name('vacancies.store');
            Route::post('/vacancies/{vacancy}', [ContentController::class, 'updateVacancy'])->name('vacancies.update');
            Route::delete('/vacancies/{vacancy}', [ContentController::class, 'destroyVacancy'])->name('vacancies.destroy');

            Route::post('/tenders', [ContentController::class, 'storeTender'])->name('tenders.store');
            Route::post('/tenders/{tender}', [ContentController::class, 'updateTender'])->name('tenders.update');
            Route::delete('/tenders/{tender}', [ContentController::class, 'destroyTender'])->name('tenders.destroy');

            // System Users Management
            Route::get('/users-mgmt', [ItDashboardController::class, 'users'])->name('users-mgmt');
            Route::post('/user/{user}/role', [ItDashboardController::class, 'editUserRole'])->name('user.role');
            Route::post('/user/{user}/activate', [ItDashboardController::class, 'activateUser'])->name('user.activate');
            Route::post('/user/{user}/delete', [ItDashboardController::class, 'deleteUser'])->name('user.delete');
            Route::post('/user/{user}/resend-activation', [ItDashboardController::class, 'resendActivation'])->name('user.resend_activation');
            Route::post('/user/{user}/suspend', [ItDashboardController::class, 'suspendUser'])->name('user.suspend');
            Route::post('/user/{user}/reset-password', [ItDashboardController::class, 'forcePasswordReset'])->name('user.reset_password');

            // Technical Administrative Routes
            Route::get('/roles-mgmt', [ItDashboardController::class, 'roles'])->name('roles-mgmt');
            Route::get('/security-mgmt', [ItDashboardController::class, 'security'])->name('security-mgmt');
            Route::get('/templates-mgmt', [ItDashboardController::class, 'templates'])->name('templates-mgmt');
            Route::get('/printers-mgmt', [ItDashboardController::class, 'printers'])->name('printers-mgmt');
            Route::get('/numbering-mgmt', [ItDashboardController::class, 'numbering'])->name('numbering-mgmt');
            Route::get('/categories-mgmt', [ItDashboardController::class, 'categories'])->name('categories-mgmt');
            Route::get('/regions-mgmt', [ItDashboardController::class, 'regions'])->name('regions-mgmt');
            Route::get('/document-settings-mgmt', [ItDashboardController::class, 'documentSettings'])->name('document-settings-mgmt');
            Route::get('/qr-security-mgmt', [ItDashboardController::class, 'qrSecurity'])->name('qr-security-mgmt');
            Route::get('/audit-mgmt', [ItDashboardController::class, 'audit'])->name('audit-mgmt');
            Route::get('/reports-mgmt', [ItDashboardController::class, 'reports'])->name('reports-mgmt');
            Route::get('/backup-mgmt', [ItDashboardController::class, 'backup'])->name('backup-mgmt');
            Route::get('/system-mgmt', [ItDashboardController::class, 'system'])->name('system-mgmt');

            // Enhanced IT Dashboard Pages
            Route::get('/monitoring', [ItDashboardController::class, 'monitoring'])->name('monitoring');
            Route::post('/monitoring/alerts', [ItDashboardController::class, 'saveMonitoringAlerts'])->name('monitoring.alerts.save');
            Route::post('/monitoring/alerts/test', [ItDashboardController::class, 'testMonitoringAlerts'])->name('monitoring.alerts.test');
            Route::get('/incidents', [ItDashboardController::class, 'incidents'])->name('incidents');
            Route::post('/incidents', [ItDashboardController::class, 'storeIncident'])->name('incidents.store');
            Route::post('/incidents/{incident}/update', [ItDashboardController::class, 'updateIncident'])->name('incidents.update');
            Route::post('/incidents/{incident}/helpdesk-ticket', [ItDashboardController::class, 'createTicketFromIncident'])->middleware('role:it_admin')->name('incidents.ticket');
            Route::get('/drafts', [ItDashboardController::class, 'drafts'])->name('drafts');
            Route::get('/intake', [ItDashboardController::class, 'intake'])->name('intake');
            Route::get('/intake/create', [ItDashboardController::class, 'physicalIntakeForm'])->name('intake.create');
            Route::post('/intake/process', [ItDashboardController::class, 'physicalIntakeProcess'])->name('intake.process');
            Route::get('/intake/{application}/edit', [ItDashboardController::class, 'intakeEdit'])->name('intake.edit');
            Route::post('/intake/{application}/update', [ItDashboardController::class, 'intakeUpdate'])->name('intake.update');
            Route::get('/records', [ItDashboardController::class, 'records'])->name('records');
            Route::get('/helpdesk', [ItDashboardController::class, 'helpdesk'])->middleware('role:it_admin')->name('helpdesk');
            Route::post('/helpdesk', [ItDashboardController::class, 'storeTicket'])->middleware('role:it_admin')->name('helpdesk.store');
            Route::post('/helpdesk/{ticket}/update', [ItDashboardController::class, 'updateTicket'])->middleware('role:it_admin')->name('helpdesk.update');
            Route::get('/helpdesk/{ticket}/attachment', [ItDashboardController::class, 'downloadTicketAttachment'])->middleware('role:it_admin')->name('helpdesk.attachment');
            Route::get('/devices', [ItDashboardController::class, 'devices'])->middleware('role:it_admin')->name('devices');
            Route::post('/devices', [ItDashboardController::class, 'storeDevice'])->middleware('role:it_admin')->name('devices.store');
            Route::post('/devices/{device}/token', [ItDashboardController::class, 'rotateDeviceToken'])->middleware('role:it_admin')->name('devices.token');
            Route::get('/performance', [ItDashboardController::class, 'performance'])->name('performance');
            Route::get('/temp-roles', [ItDashboardController::class, 'tempRoles'])->name('temp-roles');
            Route::post('/temp-role', [ItDashboardController::class, 'storeTempRole'])->name('temp-role.store');
            Route::post('/temp-role/{assignment}/revoke', [ItDashboardController::class, 'revokeTempRole'])->name('temp-role.revoke');
            Route::get('/locations-mgmt', [ItDashboardController::class, 'locationsMgmt'])->name('locations-mgmt');
            Route::post('/locations', [ItDashboardController::class, 'storeLocation'])->name('locations.store');
            Route::post('/locations/{location}/toggle', [ItDashboardController::class, 'toggleLocation'])->name('locations.toggle');

            // Global configurations actions
            Route::post('/config/save', [ItDashboardController::class, 'saveConfig'])->name('config.save');
            Route::post('/system/backup', [ItDashboardController::class, 'triggerBackup'])->name('system.backup');
            Route::post('/system/clear-cache', [ItDashboardController::class, 'clearCache'])->name('system.clear_cache');
            Route::post('/system/cleanup', [ItDashboardController::class, 'runCleanup'])->name('system.cleanup');
            Route::post('/security/session/{id}/logout', [ItDashboardController::class, 'logoutSession'])->name('security.session.logout');
            Route::post('/security/ip/block', [ItDashboardController::class, 'blockIp'])->name('security.ip.block');
            Route::post('/security/ip/{block}/unblock', [ItDashboardController::class, 'unblockIp'])->name('security.ip.unblock');
            Route::post('/security/toggle-rate-limiting', [ItDashboardController::class, 'toggleRateLimiting'])->name('security.toggle_rate_limiting');
            Route::post('/security/ssl-audit', [ItDashboardController::class, 'sslAudit'])->name('security.ssl_audit');
            Route::get('/reports/generate/{type}', [ItDashboardController::class, 'generateReport'])->name('reports.generate');

            // Form Builder
            Route::get('/form-builder', [FormBuilderController::class, 'index'])->name('form-builder');
            Route::get('/form-builder/preview', [FormBuilderController::class, 'preview'])->name('form-builder.preview');
            Route::post('/form-builder', [FormBuilderController::class, 'store'])->name('form-builder.store');
            Route::post('/form-builder/{field}/update', [FormBuilderController::class, 'update'])->name('form-builder.update');
            Route::post('/form-builder/{field}/delete', [FormBuilderController::class, 'destroy'])->name('form-builder.destroy');
            Route::post('/form-builder/reorder', [FormBuilderController::class, 'reorder'])->name('form-builder.reorder');
            Route::post('/form-builder/{field}/toggle', [FormBuilderController::class, 'toggleActive'])->name('form-builder.toggle');
            Route::post('/form-builder/publish', [FormBuilderController::class, 'publish'])->name('form-builder.publish');
            Route::post('/form-builder/revisions/{revision}/restore', [FormBuilderController::class, 'restoreRevision'])->name('form-builder.restore');

            // Dashboard Builder
            Route::get('/dashboard-builder', [DashboardBuilderController::class, 'index'])->name('dashboard-builder');
            Route::get('/dashboard-builder/create', [DashboardBuilderController::class, 'create'])->name('dashboard-builder.create');
            Route::post('/dashboard-builder', [DashboardBuilderController::class, 'store'])->name('dashboard-builder.store');
            Route::get('/dashboard-builder/{dashboard}/edit', [DashboardBuilderController::class, 'edit'])->name('dashboard-builder.edit');
            Route::post('/dashboard-builder/{dashboard}/update', [DashboardBuilderController::class, 'update'])->name('dashboard-builder.update');
            Route::post('/dashboard-builder/{dashboard}/delete', [DashboardBuilderController::class, 'destroy'])->name('dashboard-builder.delete');
            Route::post('/dashboard-builder/{dashboard}/widget', [DashboardBuilderController::class, 'addWidget'])->name('dashboard-builder.add-widget');
            Route::post('/dashboard-builder/{dashboard}/widget/{widget}/remove', [DashboardBuilderController::class, 'removeWidget'])->name('dashboard-builder.remove-widget');
            Route::get('/dashboard-builder/{dashboard}/preview', [DashboardBuilderController::class, 'preview'])->name('dashboard-builder.preview');

            // Roles & Permissions
            Route::get('/roles-permissions', [RolesPermissionsController::class, 'index'])->name('roles-permissions');
            Route::post('/roles-permissions/role', [RolesPermissionsController::class, 'createRole'])->name('roles-permissions.create-role');
            Route::get('/roles-permissions/role/{role}/edit', [RolesPermissionsController::class, 'editRole'])->name('roles-permissions.edit-role');
            Route::post('/roles-permissions/role/{role}/update', [RolesPermissionsController::class, 'updateRole'])->name('roles-permissions.update-role');
            Route::post('/roles-permissions/role/{role}/delete', [RolesPermissionsController::class, 'deleteRole'])->name('roles-permissions.delete-role');
            Route::post('/roles-permissions/permission', [RolesPermissionsController::class, 'createPermission'])->name('roles-permissions.create-permission');
            Route::post('/roles-permissions/permission/{permission}/delete', [RolesPermissionsController::class, 'deletePermission'])->name('roles-permissions.delete-permission');
            Route::post('/roles-permissions/assign-role', [RolesPermissionsController::class, 'assignRoleToUser'])->name('roles-permissions.assign-role');
            Route::post('/roles-permissions/remove-role', [RolesPermissionsController::class, 'removeRoleFromUser'])->name('roles-permissions.remove-role');

            // Legacy Redirects
            Route::get('/command-center', fn() => redirect()->route('staff.it.dashboard'));
        });

    Route::middleware(['staff.portal'])
        ->prefix('staff')
        ->name('staff.')
        ->group(function () {
            Route::get('/custom-dashboard/{slug}', [DashboardBuilderController::class, 'renderForRole'])->name('custom-dashboard');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - AUDITOR (READ ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:auditor|director|super_admin|registrar'])
        ->prefix('staff/auditor')
        ->name('staff.auditor.')
        ->group(function () {
            Route::get('/', [AuditorController::class, 'dashboard'])->name('dashboard');

            // Analytics (charts)
            Route::get('/analytics', [AuditorController::class, 'analytics'])->name('analytics');

            // User logins + full user activity trails
            Route::get('/logins', [AuditorController::class, 'logins'])->name('logins');
            Route::get('/logins.csv', [AuditorController::class, 'loginsCsv'])->name('logins.csv');

            // 1) Application & accreditation audits
            Route::get('/applications', [AuditorController::class, 'applications'])->name('applications');
            Route::get('/applications.csv', [AuditorController::class, 'applicationsCsv'])->name('applications.csv');

            // 2) Financial & payment auditing (read-only)
            Route::get('/payments/paynow', [AuditorController::class, 'paynow'])->name('paynow');
            Route::get('/payments/paynow.csv', [AuditorController::class, 'paynowCsv'])->name('paynow.csv');
            Route::get('/payments/proofs', [AuditorController::class, 'proofs'])->name('proofs');
            Route::get('/payments/proofs.csv', [AuditorController::class, 'proofsCsv'])->name('proofs.csv');
            Route::get('/payments/waivers', [AuditorController::class, 'waivers'])->name('waivers');
            Route::get('/payments/waivers.csv', [AuditorController::class, 'waiversCsv'])->name('waivers.csv');
            Route::get('/payments/reconciliation', [AuditorController::class, 'reconciliation'])->name('reconciliation');

            // 3) Complaints & Appeals oversight
            Route::get('/complaints', [AuditorController::class, 'complaints'])->name('complaints');

            // 4) Activity logs
            Route::get('/logs', [AuditorController::class, 'logs'])->name('logs');
            Route::get('/logs.csv', [AuditorController::class, 'logsCsv'])->name('logs.csv');
            Route::get('/logs.pdf', [AuditorController::class, 'logsPdf'])->name('logs.pdf');

            // 5) Reporting
            Route::get('/reports', [AuditorController::class, 'reports'])->name('reports');
            Route::get('/reports.csv', [AuditorController::class, 'reportsCsv'])->name('reports.csv');

            // 6) Security oversight
            Route::get('/security', [AuditorController::class, 'security'])->name('security');
            Route::get('/security.csv', [AuditorController::class, 'securityCsv'])->name('security.csv');

            // Consolidated findings register
            Route::get('/flags', [AuditorController::class, 'flags'])->name('flags');
            Route::post('/flags/{flag}/update', [AuditorController::class, 'updateFlag'])->name('flags.update');

            // Activity feed export
            Route::get('/activity.csv', [AuditorController::class, 'activityCsv'])->name('activity.csv');

            // Flag anomalies (Auditor-only)
            Route::post('/flag', [AuditorController::class, 'flag'])->name('flag');
        });

    /*
    |--------------------------------------------------------------------------
    | STAFF - DIRECTOR (Executive Dashboard)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['staff.portal', 'role:director|super_admin|registrar'])
        ->prefix('staff/director')
        ->name('staff.director.')
        ->group(function () {
            // Operational routes (not blocked by view_only)
            Route::get('/applications', [DirectorController::class, 'applications'])->name('applications.index');
            Route::get('/applications/{application}', [DirectorController::class, 'showApplication'])->name('applications.show');
            Route::post('/applications/{application}/approve', [DirectorController::class, 'approveApplication'])->name('applications.approve');
            Route::post('/applications/{application}/reject', [DirectorController::class, 'rejectApplication'])->name('applications.reject');
        });

    Route::middleware(['staff.portal', 'role:director|super_admin|registrar', 'director.view_only'])
        ->prefix('staff/director')
        ->name('staff.director.')
        ->group(function () {
            Route::get('/', [DirectorController::class, 'dashboard'])->name('dashboard');
            Route::get('/reports/accreditation', [DirectorController::class, 'accreditationPerformance'])->name('reports.accreditation');
            Route::get('/reports/financial', [DirectorController::class, 'financialOverview'])->name('reports.financial');
            Route::get('/reports/compliance', [DirectorController::class, 'complianceRisk'])->name('reports.compliance');
            Route::get('/reports/mediahouses', [DirectorController::class, 'mediaHouseOversight'])->name('reports.mediahouses');
            Route::get('/reports/staff', [DirectorController::class, 'staffPerformance'])->name('reports.staff');
            Route::get('/reports/issuance', [DirectorController::class, 'issuanceOversight'])->name('reports.issuance');
            Route::get('/reports/geographic', [DirectorController::class, 'geographicDistribution'])->name('reports.geographic');
            Route::get('/reports/downloads', [DirectorController::class, 'reportsDownloads'])->name('reports.downloads');

            // Report Generation Routes
            Route::post('/generate/monthly-accreditation', [DirectorController::class, 'generateMonthlyAccreditationReport'])->name('generate.monthly-accreditation');
            Route::post('/generate/revenue-financial', [DirectorController::class, 'generateRevenueFinancialReport'])->name('generate.revenue-financial');
            Route::post('/generate/compliance-audit', [DirectorController::class, 'generateComplianceAuditReport'])->name('generate.compliance-audit');
            Route::post('/generate/mediahouse-status', [DirectorController::class, 'generateMediaHouseStatusReport'])->name('generate.mediahouse-status');
            Route::post('/generate/operational-performance', [DirectorController::class, 'generateOperationalPerformanceReport'])->name('generate.operational-performance');
        });

    /*
    |--------------------------------------------------------------------------
    | CHATBOT
    |--------------------------------------------------------------------------
    */
    Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');

// Password change route for staff with temporary passwords

/*
|--------------------------------------------------------------------------
| STAFF - OPERATIONAL REPORTS (Unified)
|--------------------------------------------------------------------------
*/
Route::middleware(['staff.portal', 'role:super_admin|auditor|registrar|director|it_admin'])
    ->prefix('staff/operational-reports')
    ->name('staff.reports.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\ReportController::class, 'index'])->name('index');
        Route::get('/config', [\App\Http\Controllers\Staff\ReportController::class, 'getConfig'])->name('config');
        Route::get('/data/{tab}', [\App\Http\Controllers\Staff\ReportController::class, 'getData'])->name('data');
        Route::get('/export', [\App\Http\Controllers\Staff\ReportController::class, 'export'])->name('export');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('/staff/change-password', [\App\Http\Controllers\Staff\StaffAuthController::class, 'showPasswordChange'])->name('staff.password.change');
    Route::post('/staff/change-password', [\App\Http\Controllers\Staff\StaffAuthController::class, 'processPasswordChange'])->name('staff.password.change.update');
});

// Public Verification
Route::get('/verify/{token}', [\App\Http\Controllers\PublicVerificationController::class, 'verify'])->name('public.verify');

/*
|--------------------------------------------------------------------------
| PAYNOW PAYMENT ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Payment initiation
    Route::post('/payment/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate');
    
    // Check payment status (AJAX)
    Route::get('/payment/status', [PaymentController::class, 'checkStatus'])->name('payment.status');
});

// PayNow callback routes (public, no auth required)
Route::post('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payment/return', [PaymentController::class, 'return'])->name('payment.return');
