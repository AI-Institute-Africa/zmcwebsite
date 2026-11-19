<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PortalReceiptController extends Controller
{
    public function download(Payment $payment)
    {
        if ($payment->payer_user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if ($payment->status !== 'paid' || !$payment->receipt_number) {
            return back()->with('error', 'Receipt not available for this payment.');
        }

        $payment->load(['application.applicant', 'recorder']);
        $application = $payment->application;

        $methodLabels = [
            'paynow_reference' => 'PayNow',
            'paynow' => 'PayNow',
            'proof' => 'Proof of Payment',
            'proof_upload' => 'Proof of Payment',
            'waiver' => 'Fee Waiver',
            'cash' => 'Cash',
            'transfer' => 'Bank Transfer',
            'general' => 'Other',
        ];

        $logoPath = public_path('zmc_logo.png');
        $logoData = "";
        if (file_exists($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $data = [
            'application' => $application,
            'payment' => $payment,
            'receipt_number' => $payment->receipt_number,
            'amount' => $payment->amount,
            'payment_method' => $payment->method ?? 'N/A',
            'payment_date' => $payment->confirmed_at ?? $payment->created_at,
            'date' => now()->format('Y-m-d H:i'),
            'receipted_by' => $payment->recorder?->name ?? 'Accounts Officer',
            'generated_at' => now()->format('d M Y H:i'),
            'company_name' => 'Zimbabwe Media Commission',
            'company_address' => config('organization.registered_address'),
            'company_email' => config('organization.accreditation_email'),
            'company_phone' => config('organization.public_phone'),
            'company_logo' => $logoData,
        ];

        $pdf = Pdf::loadView('receipts.styled_receipt', $data);
        return $pdf->download('Receipt_' . $payment->receipt_number . '.pdf');
    }
}
