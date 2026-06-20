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
        .event-date {
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
            white-space: pre-wrap;
            margin-bottom: 20px;
        }
        .section-header {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 23px;
            margin: 22px 0 10px;
        }
        .match {
            border: 1px solid rgba(26, 18, 16, 0.1);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 11px;
        }
        .match-stage {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8a1f3d;
            background: rgba(138, 31, 61, 0.1);
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .match-teams {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
        }
        .match-meta {
            font-size: 12px;
            color: #6b5c53;
        }
        .cta-button {
            display: inline-block;
            background: #8a1f3d;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            margin: 20px 0;
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
            <div class="crest">🏆</div>
            <div class="event-name">{{ $eventName }}</div>
            <div class="event-date">{{ $venueName }}</div>
        </div>
        
        <div class="body">
            <div class="greeting">{!! nl2br(e($emailBody)) !!}</div>
            
            @if(count($matches) > 0)
                <div class="section-header">Your Matches</div>
                
                @foreach($matches as $match)
                    <div class="match">
                        <div class="match-stage">{{ $match['stageCode'] ?? 'Match' }}</div>
                        <div class="match-teams">{{ $match['homeTeam'] ?? 'TBD' }} vs {{ $match['awayTeam'] ?? 'TBD' }}</div>
                        <div class="match-meta">
                            🏟️ {{ $match['venueName'] ?? '' }} • {{ $match['date'] ?? '' }} • {{ $match['kickoff'] ?? '' }}
                        </div>
                    </div>
                @endforeach
            @endif
            
            <center>
                <a href="{{ $rsvpUrl }}" class="cta-button">Respond to Invitation</a>
            </center>
        </div>
        
        <div class="footer">
            This is an automated message. Please do not reply directly to this email.
        </div>
    </div>
</body>
</html>
