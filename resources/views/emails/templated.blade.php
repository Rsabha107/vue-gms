<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; color: #1a1210; margin: 0; padding: 0; background-color: #f5f0eb; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 14px -4px rgba(38, 34, 30, 0.12); }
        .header { background: linear-gradient(150deg, #8a1f3d, #5f1226); color: #ffffff; padding: 30px; text-align: center; position: relative; }
        .header::before { content: ""; position: absolute; inset: 0; background: radial-gradient(circle at 80% -10%, rgba(255,255,255,0.12), transparent 50%); }
        .crest { font-size: 34px; margin-bottom: 8px; position: relative; }
        .event-name { font-family: Georgia, serif; font-size: 28px; font-weight: 400; position: relative; }
        .body { padding: 28px 30px; }
        .body-text { font-size: 14px; line-height: 1.65; white-space: pre-line; }
        .badge { display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 6px; margin-bottom: 16px; }
        .badge-invitation { background: rgba(138, 31, 61, 0.1); color: #8a1f3d; }
        .badge-confirmation { background: rgba(63, 125, 82, 0.12); color: #3f7d52; }
        .badge-portal_access { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
        .badge-flight_confirmed { background: rgba(122, 90, 138, 0.12); color: #7a5a8a; }
        .badge-accommodation_confirmed { background: rgba(63, 125, 82, 0.12); color: #3f7d52; }
        .badge-transport_confirmed { background: rgba(176, 96, 56, 0.12); color: #b06038; }
        .match-card { border: 1px solid rgba(26,18,16,0.1); border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; }
        .match-stage { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #8a1f3d; margin-bottom: 4px; }
        .match-teams { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
        .match-meta { font-size: 12px; color: #6b5c53; }
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table td { padding: 10px 0; border-bottom: 1px solid rgba(26,18,16,0.06); font-size: 14px; vertical-align: top; }
        .detail-table tr:last-child td { border-bottom: none; }
        .detail-label { color: #6b5c53; white-space: nowrap; padding-right: 20px; width: 1%; }
        .detail-value { font-weight: 600; text-align: right; }
        .cta-btn { display: inline-block; padding: 12px 28px; background: #8a1f3d; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; margin-top: 20px; }
        .help { font-size: 13px; color: #6b5c53; margin-top: 20px; }
        .footer { padding: 14px 24px; border-top: 1px solid rgba(26,18,16,0.1); font-size: 11px; color: #a09488; text-align: center; background: rgba(245,240,235,0.5); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="crest">🏆</div>
            <div class="event-name">{{ $eventName }}</div>
        </div>

        <div class="body">
            @php
                $badgeLabels = [
                    'invitation' => 'Invitation',
                    'confirmation' => 'Attendance Confirmed',
                    'portal_access' => 'Portal Access',
                    'flight_confirmed' => 'Flight Confirmed',
                    'accommodation_confirmed' => 'Accommodation Confirmed',
                    'transport_confirmed' => 'Transport Confirmed',
                ];
            @endphp
            <span class="badge badge-{{ $templateType }}">{{ $badgeLabels[$templateType] ?? ucfirst(str_replace('_', ' ', $templateType)) }}</span>

            <div class="body-text">{!! nl2br(e($renderedBody)) !!}</div>

            @if(!empty($matches))
                <div style="margin-top: 20px; font-size: 12px; font-weight: 600; color: #6b5c53; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
                    Match Schedule ({{ count($matches) }})
                </div>
                @foreach($matches as $match)
                    <div class="match-card">
                        <div class="match-stage">{{ $match['stage'] ?? 'Match' }}</div>
                        <div class="match-teams">{{ $match['homeTeam'] ?? 'TBD' }} vs {{ $match['awayTeam'] ?? 'TBD' }}</div>
                        <div class="match-meta">
                            {{ $match['date'] ?? '' }}
                            @if(!empty($match['time'])) &middot; {{ $match['time'] }} @endif
                            @if(!empty($match['venue'])) &middot; {{ $match['venue'] }} @endif
                        </div>
                    </div>
                @endforeach
            @endif

            @if(!empty($serviceFields))
                <div style="border: 1px solid rgba(26,18,16,0.1); border-radius: 10px; padding: 16px; margin-top: 16px;">
                    <table class="detail-table">
                        @foreach($serviceFields as $label => $value)
                            @if($value)
                                <tr>
                                    <td class="detail-label">{{ $label }}</td>
                                    <td class="detail-value">{{ $value }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            @endif

            @if(!empty($portalUrl))
                <p style="font-size: 14px; margin-top: 24px;">
                    Access your personal guest portal to view your full itinerary:
                </p>
                <a href="{{ $portalUrl }}" class="cta-btn">Open Guest Portal</a>
            @endif

            @if(!empty($rsvpUrl))
                <p style="font-size: 14px; margin-top: 24px;">
                    Please respond to this invitation:
                </p>
                <a href="{{ $rsvpUrl }}" class="cta-btn">Respond to Invitation</a>
            @endif

            <p class="help">
                If you have any questions, please contact your dedicated protocol officer.
            </p>
        </div>

        <div class="footer">
            This is an automated message from {{ $eventName }}. Please do not reply directly.
        </div>
    </div>
</body>
</html>
