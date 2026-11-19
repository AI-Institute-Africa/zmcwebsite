<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\SystemConfig;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ComplaintsController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable','in:complaint,appeal'],
            'full_name' => ['required','string','max:200'],
            'email' => ['nullable','email','max:200'],
            'phone' => ['nullable','string','max:50'],
            'subject' => ['nullable','string','max:200'],
            'message' => ['required','string','max:10000'],
            'attachment' => ['nullable','file','max:10240','extensions:pdf,doc,docx,jpg,jpeg,png,webp,txt'],
        ]);

        $payload = [
            'type' => $data['type'] ?? 'complaint',
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
        ];

        if ($request->hasFile('attachment')) {
            $f = $request->file('attachment');
            $payload['attachment_path'] = $this->storePublicFile($f, 'website/complaints', FileType::safeHashName($f));
            $payload['attachment_original_name'] = $f->getClientOriginalName();
            $payload['attachment_mime'] = FileType::safeUploadedMime($f);
        }

        $complaint = Complaint::create($payload);
        $reference = 'COMP-' . now()->format('Ymd') . '-' . str_pad($complaint->id, 6, '0', STR_PAD_LEFT);
        $complaint->update(['reference_number' => $reference]);

        if (filter_var($complaint->email, FILTER_VALIDATE_EMAIL)
            && (SystemConfig::getValue('complaints.workflow', ['acknowledgement_enabled' => true])['acknowledgement_enabled'] ?? true)) {
            try {
                Mail::raw(
                    "Dear {$complaint->full_name},\n\n"
                    . "Your complaint has been received and registered by the Zimbabwe Media Commission (ZMC).\n"
                    . "Reference number: {$reference}\n"
                    . "Please keep this number for follow-up. ZMC will contact you in due course regarding your complaint.\n\n"
                    . "Regards;\nComplaints Desk\nZimbabwe Media Commission",
                    fn ($mail) => $mail->to($complaint->email)->subject('ZMC complaint received — ' . $reference)
                );
            } catch (\Throwable $mailException) {
                report($mailException);
            }
        }

        \App\Support\AuditTrail::log('complaint_create', null, ['complaint_id'=>$complaint->id,'type'=>$payload['type']]);

        return response()->json(['success'=>true,'message'=>'Received','reference'=>$reference]);
    }
}
