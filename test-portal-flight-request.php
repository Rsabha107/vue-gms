<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Services\Gms\PortalTokenService;

echo "\n=== PORTAL PHASE 2: Flight Request Testing ===\n\n";

// Get active event
$event = Event::active()->first();
if (!$event) {
    die("❌ No active event found\n");
}

echo "📅 Event: {$event->name}\n";
echo "Portal: " . ($event->portal_enabled ? "ENABLED ✓" : "disabled") . "\n\n";

// Find test guest with email
$testGuest = Guest::whereNotNull('email')
    ->where('email', '!=', '')
    ->first();

if (!$testGuest) {
    die("❌ No guest with email found\n");
}

echo "👤 Test Guest: {$testGuest->name}\n";
echo "   Email: {$testGuest->email}\n";
echo "   Ref: {$testGuest->reference_number}\n\n";

// Check if invitation exists
$invitation = Invitation::where('guest_id', $testGuest->id)
    ->where('event_id', $event->id)
    ->first();

if ($invitation) {
    echo "💌 Invitation: {$invitation->status_id}\n\n";
} else {
    echo "💌 Invitation: Not yet invited (portal will still work with signed URL)\n\n";
}

// Generate fresh portal URL
$portalUrl = PortalTokenService::generateSignedUrl($testGuest, 72);

echo "=== PORTAL URL (Valid for 72 hours) ===\n";
echo "$portalUrl\n\n";

echo "=== TESTING STEPS ===\n";
echo "1. Copy the URL above and paste it in your browser\n";
echo "2. You should see the portal dashboard with:\n";
echo "   - Welcome message for {$testGuest->name}\n";
echo "   - Invitation status card\n";
echo "   - Match schedule (if any)\n";
echo "   - Service requests section with 'Request Flight' button\n\n";

echo "3. Click 'Request Flight' button\n";
echo "4. Fill out the flight request form:\n";
echo "   - Departure City: London\n";
echo "   - Arrival City: Doha\n";
echo "   - Departure Date: " . date('Y-m-d', strtotime('+7 days')) . "\n";
echo "   - Preferred Time: 14:00\n";
echo "   - Cabin Class: Business\n";
echo "   - Passengers: 2\n";
echo "   - Special Requests: Window seat preferred\n\n";

echo "5. Click 'Submit Request'\n";
echo "6. Form should close and you should see success message\n\n";

echo "7. Verify in GMS:\n";
echo "   - Go to /gms/flights\n";
echo "   - New flight request should appear with:\n";
echo "     • Globe icon badge next to guest name\n";
echo "     • Status: New\n";
echo "     • Code: FL-XXX\n";
echo "   - Use 'Source' filter to show only 'Portal' requests\n\n";

// Check database config
echo "=== DATABASE CHECK ===\n";
$flightCount = \App\Models\FlightRequest::count();
echo "Existing flight requests: $flightCount\n";

$portalFlights = \App\Models\FlightRequest::where('source', 'portal')->count();
echo "Portal-submitted flights: $portalFlights\n\n";

echo "✅ Ready to test!\n\n";
