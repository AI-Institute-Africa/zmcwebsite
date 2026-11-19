<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Event Alert</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #9333ea 0%, #6b21a8 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">
                                🎉 New Event/Training!
                            </h1>
                            <p style="margin: 10px 0 0; color: #f3e8ff; font-size: 14px;">
                                Zimbabwe Media Commission
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px; color: #1e293b; font-size: 24px;">
                                {{ $event->title }}
                            </h2>
                            
                            @if($event->event_type)
                            <div style="margin-bottom: 15px;">
                                <span style="display: inline-block; background-color: #f3e8ff; color: #9333ea; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                    {{ ucfirst($event->event_type) }}
                                </span>
                            </div>
                            @endif
                            
                            @if($event->description)
                            <div style="margin-bottom: 25px; color: #475569; font-size: 15px; line-height: 1.6;">
                                {{ Str::limit(strip_tags($event->description), 300) }}
                            </div>
                            @endif
                            
                            <!-- Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px; background-color: #faf5ff; border-radius: 8px; padding: 20px;">
                                @if($event->starts_at)
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; font-size: 14px; width: 120px;">
                                        <strong>📅 Start Date:</strong>
                                    </td>
                                    <td style="padding: 8px 0; color: #1e293b; font-size: 14px;">
                                        {{ $event->starts_at->format('F j, Y \a\t g:i A') }}
                                    </td>
                                </tr>
                                @endif
                                
                                @if($event->ends_at)
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; font-size: 14px;">
                                        <strong>🏁 End Date:</strong>
                                    </td>
                                    <td style="padding: 8px 0; color: #1e293b; font-size: 14px;">
                                        {{ $event->ends_at->format('F j, Y \a\t g:i A') }}
                                    </td>
                                </tr>
                                @endif
                                
                                @if($event->venue)
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; font-size: 14px;">
                                        <strong>📍 Venue:</strong>
                                    </td>
                                    <td style="padding: 8px 0; color: #1e293b; font-size: 14px;">
                                        {{ $event->venue }}
                                    </td>
                                </tr>
                                @endif
                                
                                @if($event->registration_deadline)
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; font-size: 14px;">
                                        <strong>⏰ Register By:</strong>
                                    </td>
                                    <td style="padding: 8px 0; color: #dc2626; font-size: 14px; font-weight: 600;">
                                        {{ $event->registration_deadline->format('F j, Y') }}
                                    </td>
                                </tr>
                                @endif
                            </table>
                            
                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ $websiteUrl }}/events" 
                                   style="display: inline-block; background: linear-gradient(135deg, #9333ea 0%, #6b21a8 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 10px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 12px rgba(147, 51, 234, 0.3);">
                                    View Event Details
                                </a>
                            </div>
                            
                            <!-- Divider -->
                            <div style="border-top: 1px solid #e2e8f0; margin: 30px 0;"></div>
                            
                            <!-- Footer Message -->
                            <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                                You're receiving this email because you subscribed to updates from the Zimbabwe Media Commission. 
                                Stay informed about upcoming events and training opportunities!
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 10px; color: #94a3b8; font-size: 12px;">
                                © {{ date('Y') }} Zimbabwe Media Commission. All rights reserved.
                            </p>
                            <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                                {{ config('organization.registered_address') }} | {{ config('organization.legacy_phone') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
