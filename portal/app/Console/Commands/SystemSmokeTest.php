<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class SystemSmokeTest extends Command
{
    protected $signature = 'system:smoke-test';
    protected $description = 'Directly invoke every parameterless GET controller as super_admin and report failures.';

    public function handle(): int
    {
        $admin = User::where('email', 'mupikenipatience@gmail.com')->first();
        if (!$admin) {
            $this->error('Superadmin user not found.');
            return 1;
        }

        // Boot session + auth in this CLI process.
        config(['session.driver' => 'array']);
        Session::start();
        Session::put([
            'staff_authed'        => true,
            'staff_authed_at'     => now()->timestamp,
            'active_staff_role'   => 'super_admin',
            'staff_role'          => 'super_admin',
            'staff_otp_verified'  => true,
        ]);
        Auth::login($admin);

        $skipPatterns = [
            '/^_/', '/\{/', '/logout/i', '/download/i', '/export/i',
            '/print/i', '/pdf/i', '/api\//', '/storage\//', '/sanctum\//',
            '/livewire/', '/_debugbar/', '/telescope/', '/horizon/',
            '/refresh$/', '/poll/', '/callback/', '/notify/',
        ];

        $targets = [];
        foreach (Route::getRoutes() as $route) {
            if (!in_array('GET', $route->methods())) continue;
            $uri = $route->uri();
            if (str_contains($uri, '{')) continue;
            foreach ($skipPatterns as $p) {
                if (preg_match($p, '/' . $uri)) continue 2;
            }
            if (!preg_match('#^(staff/|admin/|portal/|media-house/|dashboard|home)#', $uri)) continue;
            $targets[] = $route;
        }

        $this->info('Invoking ' . count($targets) . ' controller actions as super_admin (middleware bypassed)...');

        $results = ['ok' => 0, 'redirect' => 0, 'error' => 0];
        $failures = [];

        foreach ($targets as $route) {
            /** @var RoutingRoute $route */
            $uri = '/' . ltrim($route->uri(), '/');
            $name = $route->getName() ?: '(unnamed)';

            try {
                $request = Request::create($uri, 'GET');
                $request->setUserResolver(fn() => $admin);
                $request->setLaravelSession(app('session.store'));
                app()->instance('request', $request);

                $response = $route->bind($request)->run();

                if ($response instanceof \Symfony\Component\HttpFoundation\RedirectResponse) {
                    $results['redirect']++;
                    continue;
                }

                if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {
                    $code = $response->getStatusCode();
                    if ($code >= 200 && $code < 300) $results['ok']++;
                    elseif ($code >= 300 && $code < 400) $results['redirect']++;
                    else { $results['error']++; $failures[] = "[$code] $uri ($name)"; }
                } else {
                    // Views, arrays, strings — assume OK
                    $results['ok']++;
                }
            } catch (\Throwable $e) {
                $results['error']++;
                $msg = substr(str_replace("\n", ' ', $e->getMessage()), 0, 160);
                $failures[] = "[EX] $uri ($name) — " . class_basename($e) . ': ' . $msg;
            }
        }

        $this->newLine();
        $this->line("OK:        " . $results['ok']);
        $this->line("Redirect:  " . $results['redirect']);
        $this->line("Errors:    " . $results['error']);

        if (!empty($failures)) {
            $this->newLine();
            $this->warn('FAILURES:');
            foreach ($failures as $f) $this->line('  ' . $f);
        }

        return ($results['error'] === 0) ? 0 : 2;
    }
}
