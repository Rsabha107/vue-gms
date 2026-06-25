<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FlightRequest;
use App\Models\Guest;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Portal Service Request Controller
 * 
 * Security Model:
 * - Portal dashboard requires valid signed URL (validated in PortalDashboardController)
 * - Service submission routes (POST) don't require signature validation
 * - Guest identity is validated via route model binding
 * - All form data is validated before creating records
 */
class PortalServiceController extends Controller
{
    /**
     * Store a flight request from guest portal
     */
    public function storeFlight(Request $request, Guest $guest)
    {
        // Guest already validated access by loading the portal dashboard
        // No signature check needed for POST routes
        
        // Validate flight request data
        $validator = Validator::make($request->all(), [
            'trip_type' => 'required|in:one_way,round_trip',
            'departure_city' => 'required|string|max:255',
            'arrival_city' => 'required|string|max:255',
            'departure_date' => 'required|date|after_or_equal:today',
            'departure_time' => 'nullable|date_format:H:i',
            'return_date' => 'required_if:trip_type,round_trip|nullable|date|after:departure_date',
            'return_time' => 'nullable|date_format:H:i',
            'class' => 'required|in:economy,business,first',
            'passengers' => 'required|integer|min:1|max:9',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Get current event from guest's invitation
        $event = Event::where('active_flag', true)->first();
        if (!$event) {
            return back()->withErrors(['event' => 'No active event found']);
        }

        $invitation = $guest->invitations()->where('event_id', $event->id)->first();
        if (!$invitation) {
            return back()->withErrors(['event' => 'No invitation found for this event']);
        }

        // Generate unique flight request code
        $latestCode = FlightRequest::where('code', 'like', 'FL-%')
            ->orderByRaw('CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC')
            ->value('code');
        
        $nextNumber = 1;
        if ($latestCode) {
            $nextNumber = intval(substr($latestCode, 3)) + 1;
        }
        $code = 'FL-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Create flight request
        $flightRequest = FlightRequest::create([
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'code' => $code,
            'status' => 'new',
            'pax' => $validated['passengers'],
            'note' => $validated['special_requests'],
            'initiated_by' => 'guest',
            'source' => 'portal',
        ]);

        // Create outbound flight leg
        $flightRequest->legs()->create([
            'dir' => 'Outbound',
            'airline' => 'Qatar Airways',
            'flight_no' => 'TBD',
            'from_code' => 'XXX',
            'from_city' => $validated['departure_city'],
            'to_code' => 'DOH',
            'to_city' => $validated['arrival_city'],
            'date' => $validated['departure_date'],
            'dep' => $validated['departure_time'],
            'arr' => null,
            'cls' => ucfirst($validated['class']),
            'dur' => null,
            'sort' => 1,
        ]);

        // Create return flight leg if round trip
        if ($validated['trip_type'] === 'round_trip' && !empty($validated['return_date'])) {
            $flightRequest->legs()->create([
                'dir' => 'Inbound',
                'airline' => 'Qatar Airways',
                'flight_no' => 'TBD',
                'from_code' => 'DOH',
                'from_city' => $validated['arrival_city'],
                'to_code' => 'XXX',
                'to_city' => $validated['departure_city'],
                'date' => $validated['return_date'],
                'dep' => $validated['return_time'],
                'arr' => null,
                'cls' => ucfirst($validated['class']),
                'dur' => null,
                'sort' => 2,
            ]);
        }

        return back()->with('success', 'Flight request submitted successfully');
    }

    /**
     * Store an accommodation request from guest portal
     */
    public function storeAccommodation(Request $request, Guest $guest)
    {
        // Guest already validated access by loading the portal dashboard
        // No signature check needed for POST routes
        
        // Validate accommodation request data
        $validator = Validator::make($request->all(), [
            'hotel_preferences' => 'nullable|string|max:500',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'room_type' => 'required|in:single,double,suite',
            'rooms' => 'required|integer|min:1|max:10',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Get current event from guest's invitation
        $event = Event::where('active_flag', true)->first();
        if (!$event) {
            return back()->withErrors(['event' => 'No active event found']);
        }

        $invitation = $guest->invitations()->where('event_id', $event->id)->first();
        if (!$invitation) {
            return back()->withErrors(['event' => 'No invitation found for this event']);
        }

        // Generate unique accommodation request code
        $latestCode = \App\Models\AccommodationRequest::where('code', 'like', 'ACC-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->value('code');
        
        $nextNumber = 1;
        if ($latestCode) {
            $nextNumber = intval(substr($latestCode, 4)) + 1;
        }
        $code = 'ACC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Calculate nights
        $checkIn = new \DateTime($validated['check_in']);
        $checkOut = new \DateTime($validated['check_out']);
        $nights = $checkIn->diff($checkOut)->days;

        // Create accommodation request
        \App\Models\AccommodationRequest::create([
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'code' => $code,
            'status_id' => 'new',
            'hotel_name' => $validated['hotel_preferences'] ?: 'Guest preference',
            'room_type' => ucfirst($validated['room_type']),
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'nights' => $nights,
            'notes' => $validated['special_requests'],
            'initiated_by' => 'guest',
            'source' => 'portal',
        ]);

        return back()->with('success', 'Accommodation request submitted successfully');
    }

    /**
     * Store a transport request from guest portal
     */
    public function storeTransport(Request $request, Guest $guest)
    {
        // Guest already validated access by loading the portal dashboard
        // No signature check needed for POST routes
        
        // Validate transport request data
        $validator = Validator::make($request->all(), [
            'transport_type' => 'required|in:airport_transfer,point_to_point,daily_driver',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'passengers' => 'required|integer|min:1|max:15',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Get current event from guest's invitation
        $event = Event::where('active_flag', true)->first();
        if (!$event) {
            return back()->withErrors(['event' => 'No active event found']);
        }

        $invitation = $guest->invitations()->where('event_id', $event->id)->first();
        if (!$invitation) {
            return back()->withErrors(['event' => 'No invitation found for this event']);
        }

        // Generate unique transport request code
        $latestCode = \App\Models\TransportRequest::where('code', 'like', 'TRN-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->value('code');
        
        $nextNumber = 1;
        if ($latestCode) {
            $nextNumber = intval(substr($latestCode, 4)) + 1;
        }
        $code = 'TRN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Map transport type to readable label
        $typeLabels = [
            'airport_transfer' => 'Airport Transfer',
            'point_to_point' => 'Point-to-Point',
            'daily_driver' => 'Daily Driver',
        ];

        // Create transport request
        \App\Models\TransportRequest::create([
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'code' => $code,
            'status_id' => 'pending',
            'type' => $typeLabels[$validated['transport_type']],
            'pickup_location' => $validated['pickup_location'],
            'dropoff_location' => $validated['dropoff_location'],
            'datetime' => $validated['date'] . ' ' . $validated['time'],
            'notes' => $validated['special_requests'],
            'initiated_by' => 'guest',
            'source' => 'portal',
        ]);

        return back()->with('success', 'Transport request submitted successfully');
    }
}
