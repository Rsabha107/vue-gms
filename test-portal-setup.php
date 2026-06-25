<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PORTAL SETUP CHECK ===\n\n";

// Check active event
$event = \App\Models\Event::where('active_flag', true)->first();
if ($event) {
    echo "✓ Active Event: {$event->name}\n";
    echo "  Portal Enabled: " . ($event->portal_enabled ? "YES ✓" : "NO ✗") . "\n";
    echo "  Auth Mode: " . ($event->portal_auth_mode ?? 'not set') . "\n\n";
} else {
    echo "✗ No active event found\n\n";
}

// Check guests with email
$guestsWithEmail = \App\Models\Guest::whereNotNull('email')->count();
echo "✓ Guests with email: {$guestsWithEmail}\n";

if ($guestsWithEmail > 0) {
    $guest = \App\Models\Guest::whereNotNull('email')->first();
    echo "  Sample: {$guest->name} ({$guest->email})\n";
    echo "  Guest ID: {$guest->id}\n\n";
}

// Check mail configuration
echo "✓ Mail Configuration:\n";
echo "  Driver: " . config('mail.default') . "\n";
echo "  Host: " . config('mail.mailers.smtp.host') . "\n";
echo "  From: " . config('mail.from.address') . "\n\n";

// Check queue
$jobsCount = \DB::table('jobs')->count();
$failedCount = \DB::table('failed_jobs')->count();
echo "✓ Queue Status:\n";
echo "  Pending jobs: {$jobsCount}\n";
echo "  Failed jobs: {$failedCount}\n\n";

// Check if guest has invitation
if ($guestsWithEmail > 0) {
    $guest = \App\Models\Guest::whereNotNull('email')->first();
    $invitation = \App\Models\Invitation::where('guest_id', $guest->id)
        ->where('event_id', $event->id)
        ->first();
    
    if ($invitation) {
        echo "✓ Test Guest has invitation\n";
        echo "  Portal sent: " . ($invitation->portal_sent_at ?? 'Not sent') . "\n";
        echo "  Portal accessed: " . ($invitation->portal_accessed_at ?? 'Never') . "\n\n";
    } else {
        echo "ℹ Test Guest NOT on event roster yet (expected for new setup)\n\n";
    }
}

echo "=== READY TO TEST ===\n";
echo "1. Visit: http://localhost/gms/settings\n";
echo "2. Go to 'Modules' section\n";
echo "3. Enable 'Guest Self-Service Portal'\n";
echo "4. Click 'Save'\n";
echo "5. Visit: http://localhost/gms/invitations\n";
echo "6. Add guest to roster if needed\n";
echo "7. Click ⋮ menu → 'Send portal link'\n";
