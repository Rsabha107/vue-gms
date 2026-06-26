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
        .greeting { font-size: 15px; margin-bottom: 20px; }
        .badge { display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 6px; margin-bottom: 16px; }
        .badge-flight { background: rgba(122, 90, 138, 0.12); color: #7a5a8a; }
        .badge-accommodation { background: rgba(63, 125, 82, 0.12); color: #3f7d52; }
        .badge-transport { background: rgba(176, 96, 56, 0.12); color: #b06038; }
        .badge-change { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
        .card { border: 1px solid rgba(26,18,16,0.1); border-radius: 10px; padding: 16px; margin-bottom: 12px; }
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table td { padding: 10px 0; border-bottom: 1px solid rgba(26,18,16,0.06); font-size: 14px; vertical-align: top; }
        .detail-table tr:last-child td { border-bottom: none; }
        .detail-label { color: #6b5c53; white-space: nowrap; padding-right: 20px; width: 1%; }
        .detail-value { font-weight: 600; text-align: right; }
        .note { font-size: 13px; color: #6b5c53; font-style: italic; margin-top: 16px; padding: 12px 14px; background: #f5f0eb; border-radius: 8px; }
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
            <div class="greeting">Dear {{ $guestName }},</div>

            <span class="badge badge-{{ strtolower($serviceType) }}">{{ $serviceType }} {{ $details['action'] ?? 'Confirmed' }}</span>

            @if(!empty($details['message']))
                <p style="font-size: 14px; margin-bottom: 18px;">{{ $details['message'] }}</p>
            @endif

            <div class="card">
                <table class="detail-table">
                    @foreach($details['fields'] ?? [] as $label => $value)
                        @if($value)
                            <tr>
                                <td class="detail-label">{{ $label }}</td>
                                <td class="detail-value">{{ $value }}</td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            </div>

            @if(!empty($details['note']))
                <div class="note">{{ $details['note'] }}</div>
            @endif

            <p style="font-size: 13px; color: #6b5c53; margin-top: 20px;">
                If you have any questions, please contact your dedicated protocol officer.
            </p>
        </div>

        <div class="footer">
            This is an automated confirmation from {{ $eventName }}. Please do not reply directly.
        </div>
    </div>
</body>
</html>
