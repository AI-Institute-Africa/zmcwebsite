<?php

use App\Http\Controllers\Api\DeviceHeartbeatController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/devices/{device}/heartbeat', DeviceHeartbeatController::class)
    ->whereNumber('device')
    ->middleware('throttle:120,1')
    ->name('api.devices.heartbeat');
