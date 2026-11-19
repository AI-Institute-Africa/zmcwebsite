<?php

namespace App\Http\Middleware;

use App\Models\RateLimitEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

class TrackThrottleRequests extends ThrottleRequests
{
    protected function buildException($request, $key, $maxAttempts, $responseCallback = null)
    {
        if ($request instanceof Request) {
            RateLimitEvent::record($request);
        }

        return parent::buildException($request, $key, $maxAttempts, $responseCallback);
    }
}
