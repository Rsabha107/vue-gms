<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIND PORTAL URL ===\n\n";

// Get the most recent guest with portal access
$invitation = \DB::table('invitations')
    ->whereNotNull('portal_sent_at')
    ->orderBy('portal_sent_at', 'desc')
    ->first();

if ($invitation) {
    $guest = \App\Models\Guest::find($invitation->guest_id);
    $event = \App\Models\Event::find($invitation->event_id);
    
    echo "Last portal link sent:\n";
    echo "Guest: {$guest->name}\n";
    echo "Email: {$guest->email}\n";
    echo "Event: {$event->name}\n";
    echo "Sent: {$invitation->portal_sent_at}\n";
    echo "Expires: {$invitation->portal_token_expires_at}\n\n";
    
    // Generate fresh portal URL
    $portalUrl = \App\Services\Gms\PortalTokenService::generateSignedUrl($guest, 72);
    
    echo "Portal URL (valid for 72 hours):\n";
    echo "→ {$portalUrl}\n\n";
    
    echo "Copy this URL to your browser to test the portal!\n";
} else {
    echo "No portal links sent yet.\n";
    echo "Send a portal link from: http://localhost/gms/invitations\n";
}
