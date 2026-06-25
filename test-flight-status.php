<?php

/**
 * Test script: Create a flight request directly and check its status
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FlightRequest;
use App\Models\Event;
use App\Models\Guest;

echo "\n";
echo "Testing Flight Request Status...\n\n";

$guest = Guest::first();
$event = Event::where('active_flag', true)->first();

if (!$guest || !$event) {
    echo "❌ Missing guest or event\n\n";
    exit(1);
}

// Create a test flight request with explicit status = 'new'
$flightRequest = FlightRequest::create([
    'guest_id' => $guest->id,
    'event_id' => $event->id,
    'code' => 'FL-TEST-' . time(),
    'status' => 'new',
    'pax' => 1,
    'note' => 'Test flight request',
    'initiated_by' => 'guest',
    'source' => 'portal',
]);

echo "✅ Created flight request: {$flightRequest->code}\n";
echo "   Guest: {$guest->name}\n";
echo "   Event: {$event->name}\n";
echo "   Status in model: {$flightRequest->status}\n\n";

// Refresh from database
$flightRequest->refresh();
echo "   Status after refresh: {$flightRequest->status}\n\n";

// Check database directly
$dbRecord = \DB::table('flight_requests')->where('code', $flightRequest->code)->first();
echo "   Status in database: {$dbRecord->status}\n\n";

if ($flightRequest->status !== 'new') {
    echo "❌ Status is '{$flightRequest->status}' instead of 'new'!\n";
    echo "   This indicates something is modifying the status.\n\n";
} else {
    echo "✅ Status is correctly 'new'\n\n";
}

// Clean up
$flightRequest->delete();
echo "🧹 Test record deleted\n\n";
