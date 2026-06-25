<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Services\Gms\PortalTokenService;

echo "\n=== PORTAL PHASE 2 COMPLETE: All Service Request Forms ===\n\n";

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

// Generate fresh portal URL
$portalUrl = PortalTokenService::generateSignedUrl($testGuest, 72);

echo "=== PORTAL URL (Valid for 72 hours) ===\n";
echo "$portalUrl\n\n";

echo "=== PHASE 2 COMPLETE FEATURES ===\n\n";

echo "✅ Flight Request Form\n";
echo "   • Departure/Arrival Cities\n";
echo "   • Date & Time\n";
echo "   • Cabin Class (Economy/Business/First)\n";
echo "   • Passenger Count\n";
echo "   • Special Requests\n\n";

echo "✅ Accommodation Request Form\n";
echo "   • Hotel Preferences\n";
echo "   • Check-In/Check-Out Dates\n";
echo "   • Room Type (Single/Double/Suite)\n";
echo "   • Number of Rooms\n";
echo "   • Special Requests\n\n";

echo "✅ Transport Request Form\n";
echo "   • Transport Type (Airport Transfer/Point-to-Point/Daily Driver)\n";
echo "   • Pickup/Dropoff Locations\n";
echo "   • Date & Time\n";
echo "   • Number of Passengers\n";
echo "   • Special Requests\n\n";

echo "=== TESTING INSTRUCTIONS ===\n\n";

echo "1. Open the portal URL above in your browser\n";
echo "2. You'll see THREE service request buttons:\n";
echo "   • Request Flight\n";
echo "   • Request Hotel\n";
echo "   • Request Transport\n\n";

echo "3. Test each form with sample data:\n\n";

echo "   📋 FLIGHT REQUEST:\n";
echo "   - Departure: London\n";
echo "   - Arrival: Doha\n";
echo "   - Date: " . date('Y-m-d', strtotime('+7 days')) . "\n";
echo "   - Time: 14:00\n";
echo "   - Class: Business\n";
echo "   - Passengers: 2\n\n";

echo "   📋 HOTEL REQUEST:\n";
echo "   - Hotel: Four Seasons Doha\n";
echo "   - Check-in: " . date('Y-m-d', strtotime('+5 days')) . "\n";
echo "   - Check-out: " . date('Y-m-d', strtotime('+10 days')) . "\n";
echo "   - Room Type: Suite\n";
echo "   - Rooms: 1\n\n";

echo "   📋 TRANSPORT REQUEST:\n";
echo "   - Type: Airport Transfer\n";
echo "   - Pickup: Hamad International Airport\n";
echo "   - Dropoff: Four Seasons Hotel\n";
echo "   - Date: " . date('Y-m-d', strtotime('+5 days')) . "\n";
echo "   - Time: 15:30\n";
echo "   - Passengers: 2\n\n";

echo "4. Verify in GMS Admin:\n\n";

echo "   ✓ Visit /gms/flights\n";
echo "     • Look for globe 🌐 badge next to guest name\n";
echo "     • Filter by 'Source: Portal'\n";
echo "     • Status should be 'New'\n";
echo "     • Code: FL-XXX\n\n";

echo "   ✓ Visit /gms/accommodation\n";
echo "     • Look for globe 🌐 badge next to guest name\n";
echo "     • Filter by 'Source: Portal'\n";
echo "     • Status should be 'New'\n";
echo "     • Code: ACC-XXX\n\n";

echo "   ✓ Visit /gms/transport\n";
echo "     • Look for globe 🌐 badge next to guest name\n";
echo "     • Filter by 'Source: Portal'\n";
echo "     • Status should be 'Pending'\n";
echo "     • Code: TRN-XXX\n\n";

// Check database config
echo "=== DATABASE STATUS ===\n";
$flightCount = \App\Models\FlightRequest::count();
$accommodationCount = \App\Models\AccommodationRequest::count();
$transportCount = \App\Models\TransportRequest::count();

echo "Existing Requests:\n";
echo "  • Flights: $flightCount\n";
echo "  • Accommodation: $accommodationCount\n";
echo "  • Transport: $transportCount\n\n";

$portalFlights = \App\Models\FlightRequest::where('source', 'portal')->count();
$portalAccommodation = \App\Models\AccommodationRequest::where('source', 'portal')->count();
$portalTransport = \App\Models\TransportRequest::where('source', 'portal')->count();

echo "Portal-Submitted:\n";
echo "  • Flights: $portalFlights\n";
echo "  • Accommodation: $portalAccommodation\n";
echo "  • Transport: $portalTransport\n\n";

echo "✅ PHASE 2 COMPLETE - Ready to test all service forms!\n\n";
