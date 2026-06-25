<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1a1210;
            margin: 0;
            padding: 0;
            background-color: #f5f0eb;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 14px -4px rgba(38, 34, 30, 0.12);
        }
        .header {
            background: linear-gradient(150deg, #8a1f3d, #5f1226);
            color: #ffffff;
            padding: 34px 30px 30px;
            text-align: center;
            position: relative;
        }
        .header::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 80% -10%, rgba(255, 255, 255, 0.12), transparent 50%);
        }
        .crest {
            font-size: 38px;
            margin-bottom: 10px;
            position: relative;
        }
        .event-name {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 36px;
            line-height: 1.02;
            font-weight: 400;
            position: relative;
        }
        .event-subtitle {
            font-size: 13px;
            opacity: 0.85;
            margin-top: 8px;
            letter-spacing: 0.5px;
            position: relative;
        }
        .body {
            padding: 26px 30px 30px;
        }
        .greeting {
            font-size: 14px;
            line-height: 1.65;
            margin-bottom: 20px;
        }
        .info-box {
            background: rgba(138, 31, 61, 0.04);
            border: 1px solid rgba(138, 31, 61, 0.12);
            border-radius: 10px;
            padding: 16px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }
        .info-label {
            color: #6b5c53;
        }
        .info-value {
            font-weight: 600;
        }
        .warning-box {
            background: #fef3cd;
            border: 1px solid #ffd93d;
            border-radius: 8px;
            padding: 14px;
            margin: 20px 0;
            font-size: 12px;
            color: #856404;
        }
        .cta-button {
            display: inline-block;
            background: #8a1f3d;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            margin: 24px 0;
            font-size: 15px;
        }
        .cta-button:hover {
            background: #6f182f;
        }
        .features {
            margin: 24px 0;
        }
        .feature {
            display: flex;
            align-items: start;
            margin-bottom: 14px;
        }
        .feature-icon {
            font-size: 20px;
            margin-right: 12px;
        }
        .feature-text {
            font-size: 13px;
            line-height: 1.5;
        }
        .feature-title {
            font-weight: 600;
            margin-bottom: 2px;
        }
        .footer {
            padding: 14px 24px;
            border-top: 1px solid rgba(26, 18, 16, 0.1);
            font-size: 11px;
            color: #a09488;
            text-align: center;
            background: rgba(245, 240, 235, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="crest">🌐</div>
            <div class="event-name">Guest Portal Access</div>
            <div class="event-subtitle">{{ $eventName }}</div>
        </div>
        
        <div class="body">
            <div class="greeting">
                Dear {{ $guestName }},
            </div>
            
            <div class="greeting">
                Welcome to your personal guest portal for <strong>{{ $eventName }}</strong>. We've created a secure, personalized dashboard where you can manage your itinerary and service requests.
            </div>
            
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">📅</div>
                    <div class="feature-text">
                        <div class="feature-title">View Your Itinerary</div>
                        See your match schedule and event details
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">✈️</div>
                    <div class="feature-text">
                        <div class="feature-title">Submit Service Requests</div>
                        Request flights, accommodation, and transport
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">👤</div>
                    <div class="feature-text">
                        <div class="feature-title">Manage Your Profile</div>
                        Update preferences and contact details
                    </div>
                </div>
            </div>
            
            <center>
                <a href="{{ $portalUrl }}" class="cta-button">Access Your Portal</a>
            </center>
            
            <div class="info-box">
                @if($eventLocation)
                <div class="info-row">
                    <span class="info-label">Location:</span>
                    <span class="info-value">{{ $eventLocation }}</span>
                </div>
                @endif
                @if($eventDates)
                <div class="info-row">
                    <span class="info-label">Dates:</span>
                    <span class="info-value">{{ $eventDates }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Link Expires:</span>
                    <span class="info-value">{{ $expiresAt }}</span>
                </div>
            </div>
            
            <div class="warning-box">
                ⚠️ <strong>Security Notice:</strong> This link is unique to you and expires in {{ $hoursValid }} hours. Please do not share it with others. If you need a new link, contact our protocol team.
            </div>
        </div>
        
        <div class="footer">
            This is an automated message from the {{ $eventName }} Protocol Team.<br>
            If you have any questions, please contact your assigned protocol officer.
        </div>
    </div>
</body>
</html>
