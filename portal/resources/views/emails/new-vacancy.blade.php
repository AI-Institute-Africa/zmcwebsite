<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Vacancy Alert</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">
                                🎯 New Vacancy Alert!
                            </h1>
                            <p style="margin: 10px 0 0; color: #e0e7ff; font-size: 14px;">
                                Zimbabwe Media Commission
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px; color: #1e293b; font-size: 24px;">
                                {{ $vacancy->title }}
                            </h2>
                            
                            @if($vacancy->position_level)
                            <div style="margin-bottom: 15px;">
                                <span style="display: inline-block; background-color: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                    {{ $vacancy->position_level }}
                                </span>
                            </div>
                            @endif
                            
                            @if($vacancy->description)
                            <div style="margin-bottom: 25px; color: #475569; font-size: 15px; line-height: 1.6;">
                                {{ Str::limit(strip_tags($vacancy->description), 300) }}
                            </div>
                            @endif
                            
                            <!-- Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px; background-color: #f8fafc; border-radius: 8px; padding: 20px;">
                                @if($vacancy->location)
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; font-size: 14px; width: 120px;">
                                        <strong>📍 Location:</strong>
                                    </td>
                                    <td style="padding: 8px 0; color: #1e293b; font-size: 14px;">
                                        {{ $vacancy->location }}
                                    </td>
                                </tr>
                                @endif
                                
                                @if($vacancy->closing_at)
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; font-size: 14px;">
                                        <strong>⏰ Deadline:</strong>
                                    </td>
                                    <td style="padding: 8px 0; color: #dc2626; font-size: 14px; font-weight: 600;">
                                        {{ $vacancy->closing_at->format('F j, Y') }}
                                    </td>
                                </tr>
                                @endif
                                
                                @if($vacancy->published_at)
                                <tr>
                                    <td style="padding: 8px 0; color: #64748b; font-size: 14px;">
                                        <strong>📅 Posted:</strong>
                                    </td>
                                    <td style="padding: 8px 0; color: #1e293b; font-size: 14px;">
                                        {{ $vacancy->published_at->format('F j, Y') }}
                                    </td>
                                </tr>
                                @endif
                            </table>
                            
                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ $websiteUrl }}/vacancies" 
                                   style="display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 10px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                                    View Full Details & Apply
                                </a>
                            </div>
                            
                            <!-- Divider -->
                            <div style="border-top: 1px solid #e2e8f0; margin: 30px 0;"></div>
                            
                            <!-- Footer Message -->
                            <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                                You're receiving this email because you subscribed to job alerts from the Zimbabwe Media Commission. 
                                Don't want to miss future opportunities? Visit our website regularly for updates.
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
