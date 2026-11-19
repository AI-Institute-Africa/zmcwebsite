<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(Request $request)
    {
        $checks = [
            'database' => false,
            'storage'  => false,
            'queue'    => false,
            'payment_callback' => false,
        ];

        // DB
        try {
            DB::select('select 1');
            $checks['database'] = true;
        } catch (\Throwable $e) {
            $checks['database_error'] = $e->getMessage();
        }

        // Storage
        try {
            $disk = Storage::disk(config('filesystems.default', 'public'));
            $probePath = 'health/probe_' . uniqid() . '.txt';
            $disk->put($probePath, 'ok');
            $disk->get($probePath);
            $disk->delete($probePath);
            $checks['storage'] = true;
        } catch (\Throwable $e) {
            $checks['storage_error'] = $e->getMessage();
        }

        // Queue (lightweight)
        try {
            $driver = config('queue.default');
            $checks['queue_driver'] = $driver;
            if ($driver === 'database') {
                $checks['queue'] = Schema::hasTable('jobs');
            } elseif ($driver === 'redis') {
                // redis() helper exists if predis/phpredis installed
                try {
                    $pong = app('redis')->connection()->ping();
                    $checks['queue'] = (bool) $pong;
                } catch (\Throwable $e) {
                    $checks['queue_error'] = $e->getMessage();
                }
            } else {
                // sync / sqs / etc.
                $checks['queue'] = true;
            }
        } catch (\Throwable $e) {
            $checks['queue_error'] = $e->getMessage();
        }

        // Payment callback route exists
        try {
            $checks['payment_callback'] = Route::has('paynow.callback');
        } catch (\Throwable $e) {
            $checks['payment_callback_error'] = $e->getMessage();
        }

        $ok = $checks['database'] && $checks['storage'] && $checks['queue'] && $checks['payment_callback'];
        return response()->json([
            'ok' => $ok,
            'checks' => $checks,
            'time' => now()->toDateTimeString(),
        ], $ok ? 200 : 503);
    }
}
