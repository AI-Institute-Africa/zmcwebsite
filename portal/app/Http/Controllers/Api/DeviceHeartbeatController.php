<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DeviceHeartbeatController extends Controller
{
    public function __invoke(Request $request, Device $device): JsonResponse
    {
        $providedToken = (string) $request->bearerToken();
        $savedHash = (string) $device->api_token_hash;
        if ($providedToken === '' || $savedHash === '' || ! hash_equals($savedHash, hash('sha256', $providedToken))) {
            return response()->json(['message' => 'Invalid device token.'], 401);
        }

        $data = $request->validate([
            'queue_size' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'failed_jobs' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'last_printed_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $updates = [
            'last_heartbeat' => now(),
            'status' => 'online',
            'connection_status' => 'connected',
        ];

        if (array_key_exists('queue_size', $data)) {
            $updates['queue_size'] = (int) $data['queue_size'];
        }
        if (array_key_exists('failed_jobs', $data)) {
            $updates['failed_jobs'] = (int) $data['failed_jobs'];
        }
        if (! empty($data['last_printed_at'])) {
            $printedAt = Carbon::parse($data['last_printed_at']);
            if ($printedAt->isFuture()) {
                return response()->json(['message' => 'last_printed_at cannot be in the future.'], 422);
            }

            if (! $device->last_successful_job || $printedAt->greaterThan($device->last_successful_job)) {
                $updates['last_successful_job'] = $printedAt;
            }
        }

        $device->forceFill($updates)->save();

        return response()->json([
            'device_id' => $device->id,
            'connectivity' => 'connected',
            'received_at' => now()->toIso8601String(),
        ]);
    }
}
