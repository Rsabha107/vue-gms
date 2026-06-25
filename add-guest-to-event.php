<?php

/**
 * Quick fix: Add first guest to first active event
 * Run: php add-guest-to-event.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\InvitationStatus;

echo "\n";
echo "Adding guest to active event...\n\n";

// Get first active event
$event = Event::where('active_flag', true)->first();
if (!$event) {
    echo "❌ No active events found!\n\n";
    exit(1);
}

// Get first guest
$guest = Guest::first();
if (!$guest) {
    echo "❌ No guests found!\n\n";
    exit(1);
}

echo "Event: {$event->name} (ID: {$event->id})\n";
echo "Guest: {$guest->name} (Ref: {$guest->reference_number})\n\n";

// Check if already added
$existingInvitation = Invitation::where('guest_id', $guest->id)
    ->where('event_id', $event->id)
    ->first();

if ($existingInvitation) {
    echo "✅ Guest already has invitation for this event (Status: {$existingInvitation->status->name})\n\n";
    
    // Generate portal URL
    $portalUrl = \App\Services\Gms\PortalTokenService::generateSignedUrl($guest);
    echo "🌐 Portal URL:\n";
    echo "   {$portalUrl}\n\n";
    exit(0);
}

// Check if already in pivot table
$alreadyOnEvent = $guest->events()->where('event_id', $event->id)->exists();

if (!$alreadyOnEvent) {
    // Add guest to event via pivot table
    $guest->events()->attach($event->id, [
        'status' => 'confirmed',
        'added_at' => now(),
        'invited_at' => now(),
    ]);
    echo "✅ Guest added to event successfully!\n\n";
} else {
    echo "✅ Guest already on event roster\n\n";
}

// Get confirmed status ID
$confirmedStatus = InvitationStatus::where('name', 'confirmed')->first();
if (!$confirmedStatus) {
    echo "❌ 'confirmed' invitation status not found! Run: php artisan db:seed --class=InvitationStatusSeeder\n\n";
    exit(1);
}

// Create invitation record
$invitation = Invitation::create([
    'guest_id' => $guest->id,
    'event_id' => $event->id,
    'status_id' => $confirmedStatus->id,
    'invited_at' => now(),
]);

echo "✅ Invitation record created (ID: {$invitation->id})\n\n";

// Generate portal URL
$portalUrl = \App\Services\Gms\PortalTokenService::generateSignedUrl($guest);
echo "🌐 Portal URL for testing:\n";
echo "   {$portalUrl}\n\n";

echo "✅ Setup complete! You can now test service requests in the portal.\n\n";
