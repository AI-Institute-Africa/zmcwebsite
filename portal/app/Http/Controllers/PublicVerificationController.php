<?php

namespace App\Http\Controllers;

use App\Models\AccreditationRecord;
use App\Models\RegistrationRecord;
use Illuminate\Http\Request;

class PublicVerificationController extends Controller
{
    /**
     * Verify an accreditation or registration via QR token.
     */
    public function verify($token)
    {
        // Search in accreditation records first
        $record = AccreditationRecord::with(['holder', 'application'])
            ->where(function ($query) use ($token) {
                $query->where('qr_token', $token)
                    ->orWhere('certificate_no', $token)
                    ->orWhere('accreditation_number', $token)
                    ->orWhere('record_number', $token);
            })
            ->first();

        $type = 'accreditation';

        if (!$record) {
            // Search in registration records
            $record = RegistrationRecord::with(['contact', 'application'])
                ->where(function ($query) use ($token) {
                    $query->where('qr_token', $token)
                        ->orWhere('registration_no', $token)
                        ->orWhere('record_number', $token);
                })
                ->first();
            $type = 'registration';
        }

        if (!$record) {
            return view('public.verify', ['error' => 'Invalid or expired verification token.']);
        }

        return view('public.verify', compact('record', 'type'));
    }
}
