<?php

namespace App\Http\Controllers\Staff\Appeals;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\AppealCommunication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CommunicationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:public_info_compliance|super_admin|director']);
    }

    public function store(Request $request, Complaint $appeal)
    {
        if ($appeal->type !== 'appeal') {
            return back()->with('error', 'Invalid request.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        try {
            Mail::raw($validated['body'], function ($message) use ($appeal, $validated) {
                $message->to($appeal->email)
                    ->subject($validated['subject']);
            });

            AppealCommunication::create([
                'complaint_id' => $appeal->id,
                'sender_id' => Auth::id(),
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'delivery_status' => 'sent',
                'sent_at' => now(),
            ]);

            \App\Support\AuditTrail::log('appeal_communication_sent', null, [
                'appeal_id' => $appeal->id,
                'subject' => $validated['subject'],
            ]);

            return back()->with('success', 'Email sent and logged successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }
}
