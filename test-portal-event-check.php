<?php

/**
 * Test script to verify portal event and invitation setup
 * Run: php test-portal-event-check.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;
use App\Models\Guest;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  Portal Event & Invitation Check                                ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check for active events
echo "📅 Checking for active events...\n";
$activeEvents = Event::where('active_flag', true)->get();

if ($activeEvents->isEmpty()) {
    echo "❌ No active events found!\n";
    echo "   Run: php artisan db:seed --class=EventSeeder\n\n";
    exit(1);
}

echo "✅ Found " . $activeEvents->count() . " active event(s):\n";
foreach ($activeEvents as $event) {
    echo "   • {$event->name} (ID: {$event->id})\n";
}
echo "\n";

// Check first guest
echo "👤 Checking guest setup...\n";
$guest = Guest::first();

if (!$guest) {
    echo "❌ No guests found!\n";
    echo "   Create a guest first or run appropriate seeder\n\n";
    exit(1);
}

echo "✅ Guest found: {$guest->name} (ID: {$guest->id}, Ref: {$guest->reference_number})\n";
echo "\n";

// Check guest's invitations
echo "✉️  Checking guest invitations...\n";
$firstEvent = $activeEvents->first();
$invitation = $guest->invitations()->where('event_id', $firstEvent->id)->first();

if (!$invitation) {
    echo "❌ No invitation found for guest in event '{$firstEvent->name}'\n";
    echo "   The guest needs to be invited to the event first\n";
    echo "   Add guest to event via GMS → Guests → Add to event\n\n";
    exit(1);
}

echo "✅ Invitation found:\n";
echo "   • Event: {$firstEvent->name}\n";
echo "   • Status: " . ($invitation->status->name ?? 'Unknown') . "\n";
echo "   • Invited: " . ($invitation->invited_at ? $invitation->invited_at->format('Y-m-d') : 'Not yet') . "\n";
echo "\n";

// Show portal URL
echo "🌐 Portal URL for testing:\n";
$portalUrl = \App\Services\Gms\PortalTokenService::generateSignedUrl($guest);
echo "   {$portalUrl}\n";
echo "\n";

echo "✅ Setup complete! You can now test service request submission.\n";
echo "\n";
