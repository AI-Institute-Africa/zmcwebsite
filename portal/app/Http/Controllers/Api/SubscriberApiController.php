<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SubscriberWelcomeMail;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscriberApiController extends Controller
{
    /**
     * Public endpoint — the ZMC Website calls this to store new subscribers.
     * No authentication required.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:200'],
            'email'     => ['required', 'email', 'max:254'],
        ]);

        $email = strtolower(trim($data['email']));

        // Check for existing subscriber (case-insensitive)
        $existing = Subscriber::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing) {
            return response()->json([
                'alreadySubscribed' => true,
                'subscriber' => [
                    'id'        => $existing->id,
                    'full_name' => $existing->full_name,
                    'email'     => $existing->email,
                ],
            ], 200);
        }

        $subscriber = Subscriber::create([
            'full_name' => trim($data['full_name']),
            'email'     => $email,
        ]);

        // Send welcome email (fire-and-forget — ignore failures)
        try {
            Mail::to($subscriber->email)->send(new SubscriberWelcomeMail($subscriber));
        } catch (\Throwable $e) {
            // Log but don't fail the request
            \Illuminate\Support\Facades\Log::warning('Welcome email failed for ' . $subscriber->email . ': ' . $e->getMessage());
        }

        return response()->json([
            'alreadySubscribed' => false,
            'subscriber' => [
                'id'        => $subscriber->id,
                'full_name' => $subscriber->full_name,
                'email'     => $subscriber->email,
            ],
        ], 201);
    }
}
