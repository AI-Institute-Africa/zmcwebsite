<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Zimbabwe Media Commission</h1>
        <p style="color: #e0e7ff; margin: 5px 0 0 0;">FOIA Appeals Response</p>
    </div>
    
    <div style="background: white; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;">
        <p style="margin-top: 0;">Dear {{ $appeal->full_name }},</p>
        
        <p>Thank you for your Freedom of Information Act appeal. We have reviewed your submission and are providing the following response:</p>
        
        <div style="background: #f3f4f6; padding: 20px; border-left: 4px solid #3b82f6; margin: 20px 0; border-radius: 5px;">
            <p style="margin: 0; white-space: pre-wrap;">{{ $responseMessage }}</p>
        </div>
        
        @if($attachmentName)
        <div style="background: #fef3c7; padding: 15px; border-left: 4px solid #fbbf24; margin: 20px 0; border-radius: 5px;">
            <p style="margin: 0;"><strong>📎 Attachment Included:</strong> {{ $attachmentName }}</p>
        </div>
        @endif
        
        <div style="margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 5px;">
            <p style="margin: 0 0 10px 0;"><strong>Your Appeal Details:</strong></p>
            <p style="margin: 5px 0;"><strong>Appeal ID:</strong> #{{ $appeal->id }}</p>
            <p style="margin: 5px 0;"><strong>Subject:</strong> {{ $appeal->subject ?? 'FOIA Appeal' }}</p>
            <p style="margin: 5px 0;"><strong>Payment Reference:</strong> {{ $appeal->payment_ref }}</p>
            <p style="margin: 5px 0;"><strong>Submitted:</strong> {{ $appeal->created_at->format('d M Y, H:i') }}</p>
        </div>
        
        <p>If you have any questions or need further clarification, please don't hesitate to reply to this email or contact us at:</p>
        
        <div style="background: #eff6ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Email:</strong> {{ config('organization.public_email') }}</p>
            <p style="margin: 5px 0;"><strong>Phone:</strong> {{ config('organization.foia_phone') }}</p>
            <p style="margin: 5px 0;"><strong>Address:</strong> {{ config('organization.foia_address') }}</p>
        </div>
        
        <p>Best regards,</p>
        <p style="margin: 5px 0;"><strong>{{ $officer->name }}</strong><br>
        <span style="color: #6b7280;">{{ $officer->role ? ucfirst(str_replace('_', ' ', $officer->role)) : 'FOIA Appeals Officer' }}</span><br>
        <span style="color: #3b82f6;">Zimbabwe Media Commission</span></p>
    </div>
    
    <div style="text-align: center; padding: 20px; color: #6b7280; font-size: 12px;">
        <p style="margin: 5px 0;">This is an official communication from Zimbabwe Media Commission</p>
        <p style="margin: 5px 0;">© {{ date('Y') }} Zimbabwe Media Commission. All rights reserved.</p>
    </div>
</body>
</html>
