<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use App\Models\Application as ApplicationModel;
use App\Observers\ApplicationObserver;
use App\Mail\Transporters\SendPulseTransport;
use App\Utils\TotalMimeTypeReplacement;
use Symfony\Component\Mime\MimeTypes;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Console commands (Laravel 11 doesn't use app/Console/Kernel by default)
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ProcessAccreditationExpiries::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This application uses MySQL only. Remove Laravel's built-in SQLite
        // connection so stale DB_CONNECTION values cannot silently select it.
        $connections = config('database.connections', []);
        unset($connections['sqlite']);
        config(['database.connections' => $connections]);

        // Force HTTPS scheme so Laravel generates secure URLs when behind reverse proxy
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        $this->replaceSymfonyMimeGuessers();

        if (config('mail.default') === 'sendpulse') {
            Mail::extend('sendpulse', function ($config) {
                return new SendPulseTransport($config['api_key'], $config['base_url']);
            });
        }

        // Bypass FinfoMimeTypeDetector for servers without fileinfo extension
        \Illuminate\Support\Facades\Storage::extend('local', function ($app, $config) {
            $visibility = new \League\Flysystem\UnixVisibility\PortableVisibilityConverter(
                $config['permissions']['file']['public'] ?? 0644,
                $config['permissions']['file']['private'] ?? 0600,
                $config['permissions']['dir']['public'] ?? 0755,
                $config['permissions']['dir']['private'] ?? 0700,
                $config['visibility'] ?? 'private'
            );

            $adapter = new \League\Flysystem\Local\LocalFilesystemAdapter(
                $config['root'],
                $visibility,
                $config['lock'] ?? LOCK_EX,
                \League\Flysystem\Local\LocalFilesystemAdapter::DISALLOW_LINKS,
                new \League\MimeTypeDetection\ExtensionMimeTypeDetector()
            );

            return new \Illuminate\Filesystem\FilesystemAdapter(
                new \League\Flysystem\Filesystem($adapter),
                $adapter,
                $config
            );
        });

        // Set default pagination view to Bootstrap 5 (without SVG arrows)
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.bootstrap-5');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.bootstrap-5');
        
        // App locale (session overrides user)
        try {
            $sessionLocale = request()->session()->get('app_locale');
            $userLocale = Auth::check() ? (Auth::user()->locale ?? null) : null;
            $locale = $sessionLocale ?: $userLocale ?: 'en';
            App::setLocale($locale);
        } catch (\Throwable $e) {
            // ignore
        }

        // Application status/payment notifications
        ApplicationModel::observe(ApplicationObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Admin counts in STAFF sidebar (Super Admin uses staff theme)
        |--------------------------------------------------------------------------
        | We do NOT show a separate admin sidebar. Instead, we inject admin menu
        | items + live counters into the existing staff sidebar when the logged-in
        | user has the super_admin role.
        */
        View::composer('layouts.sidebar_staff', function ($view) {
            try {
                $user = Auth::user();

                if (!$user || !$user->hasAnyRole(['super_admin', 'it_admin', 'director'])) {
                    return;
                }

                // Application counts
                $mediaHouseTotal = ApplicationModel::where('application_type', 'registration')->count();
                $accreditationTotal = ApplicationModel::where('application_type', 'accreditation')->count();

                // "Pending" means anything submitted/in-review but not finalized
                $pendingStatuses = [
                    ApplicationModel::SUBMITTED,
                    ApplicationModel::OFFICER_REVIEW,
                    ApplicationModel::REGISTRAR_REVIEW,
                    ApplicationModel::ACCOUNTS_REVIEW,
                    ApplicationModel::PRODUCTION_QUEUE,
                ];

                $pendingTotal = ApplicationModel::whereIn('status', $pendingStatuses)->count();
                $pendingMediaHouse = ApplicationModel::where('application_type', 'registration')
                    ->whereIn('status', $pendingStatuses)
                    ->count();
                $pendingAccreditation = ApplicationModel::where('application_type', 'accreditation')
                    ->whereIn('status', $pendingStatuses)
                    ->count();

                $view->with('admin_sidebar_counts', [
                    'mediahouse_total' => $mediaHouseTotal,
                    'accreditation_total' => $accreditationTotal,
                    'pending_total' => $pendingTotal,
                    'pending_mediahouse' => $pendingMediaHouse,
                    'pending_accreditation' => $pendingAccreditation,
                ]);
            } catch (\Throwable $e) {
                // ignore (sidebar should still render)
            }
        });
    }

    private function replaceSymfonyMimeGuessers(): void
    {
        $mimeTypes = MimeTypes::getDefault();

        try {
            $guessers = new \ReflectionProperty(MimeTypes::class, 'guessers');
            $guessers->setAccessible(true);
            $guessers->setValue($mimeTypes, []);
        } catch (\Throwable) {
            // Symfony may change internals; our guesser still runs first and always returns a MIME.
        }

        $mimeTypes->registerGuesser(new TotalMimeTypeReplacement());
        MimeTypes::setDefault($mimeTypes);
    }
}
