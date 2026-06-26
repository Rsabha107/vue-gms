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
            'status_id' => \App\Models\InvitationStatus::where('name', 'new')->value('id'),
            'pax' => 1,
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
            'status_id' => \App\Models\InvitationStatus::where('name', 'new')->value('id'),
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
        $validator = Validator::make($request->all(), [
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $event = Event::where('active_flag', true)->first();
        if (!$event) {
            return back()->withErrors(['event' => 'No active event found']);
        }

        $invitation = $guest->invitations()->where('event_id', $event->id)->first();
        if (!$invitation) {
            return back()->withErrors(['event' => 'No invitation found for this event']);
        }

        $latestCode = \App\Models\TransportRequest::where('code', 'like', 'TRN-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->value('code');

        $nextNumber = 1;
        if ($latestCode) {
            $nextNumber = intval(substr($latestCode, 4)) + 1;
        }
        $code = 'TRN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        \App\Models\TransportRequest::create([
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'code' => $code,
            'status_id' => \App\Models\InvitationStatus::where('name', 'pending')->value('id'),
            'notes' => $validated['special_requests'],
            'initiated_by' => 'guest',
            'source' => 'portal',
        ]);

        return back()->with('success', 'Transport request submitted successfully');
    }

    // ── Flight update/delete ──

    public function updateFlight(Request $request, Guest $guest, int $id)
    {
        $flight = \App\Models\FlightRequest::where('id', $id)
            ->where('guest_id', $guest->id)
            ->where('source', 'portal')
            ->whereNull('fulfilled_by_id')
            ->firstOrFail();

        $validated = $request->validate([
            'departure_city' => 'required|string|max:100',
            'arrival_city'   => 'required|string|max:100',
            'departure_date' => 'required|date',
            'departure_time' => 'nullable|string|max:10',
            'return_date'    => 'nullable|date',
            'return_time'    => 'nullable|string|max:10',
            'class'          => 'required|in:economy,business,first',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        $flight->update(['note' => $validated['special_requests']]);

        $outbound = $flight->legs()->where('dir', 'Outbound')->first();
        if ($outbound) {
            $outbound->update([
                'from_city' => $validated['departure_city'],
                'to_city'   => $validated['arrival_city'],
                'date'      => $validated['departure_date'],
                'dep'       => $validated['departure_time'] ?? '',
                'cls'       => ucfirst($validated['class']),
            ]);
        }

        $inbound = $flight->legs()->where('dir', 'Inbound')->first();
        if ($inbound && $validated['return_date']) {
            $inbound->update([
                'from_city' => $validated['arrival_city'],
                'to_city'   => $validated['departure_city'],
                'date'      => $validated['return_date'],
                'dep'       => $validated['return_time'] ?? '',
                'cls'       => ucfirst($validated['class']),
            ]);
        }

        return back()->with('success', 'Flight request updated');
    }

    public function destroyFlight(Request $request, Guest $guest, int $id)
    {
        $flight = \App\Models\FlightRequest::where('id', $id)
            ->where('guest_id', $guest->id)
            ->where('source', 'portal')
            ->whereNull('fulfilled_by_id')
            ->firstOrFail();

        $flight->legs()->delete();
        $flight->delete();

        return back()->with('success', 'Flight request deleted');
    }

    // ── Accommodation update/delete ──

    public function updateAccommodation(Request $request, Guest $guest, int $id)
    {
        $acc = \App\Models\AccommodationRequest::where('id', $id)
            ->where('guest_id', $guest->id)
            ->where('source', 'portal')
            ->whereNull('fulfilled_by_id')
            ->firstOrFail();

        $validated = $request->validate([
            'hotel_preferences' => 'nullable|string|max:500',
            'check_in'  => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'room_type' => 'required|in:single,double,suite',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        $checkIn = new \DateTime($validated['check_in']);
        $checkOut = new \DateTime($validated['check_out']);

        $acc->update([
            'hotel_name' => $validated['hotel_preferences'] ?: 'Guest preference',
            'room_type'  => ucfirst($validated['room_type']),
            'check_in'   => $validated['check_in'],
            'check_out'  => $validated['check_out'],
            'nights'     => $checkIn->diff($checkOut)->days,
            'notes'      => $validated['special_requests'],
        ]);

        return back()->with('success', 'Accommodation request updated');
    }

    public function destroyAccommodation(Request $request, Guest $guest, int $id)
    {
        $acc = \App\Models\AccommodationRequest::where('id', $id)
            ->where('guest_id', $guest->id)
            ->where('source', 'portal')
            ->whereNull('fulfilled_by_id')
            ->firstOrFail();

        $acc->delete();

        return back()->with('success', 'Accommodation request deleted');
    }

    // ── Transport update/delete ──

    public function updateTransport(Request $request, Guest $guest, int $id)
    {
        $trans = \App\Models\TransportRequest::where('id', $id)
            ->where('guest_id', $guest->id)
            ->where('source', 'portal')
            ->whereNull('fulfilled_by_id')
            ->firstOrFail();

        $validated = $request->validate([
            'special_requests' => 'nullable|string|max:1000',
        ]);

        $trans->update(['notes' => $validated['special_requests']]);

        return back()->with('success', 'Transport request updated');
    }

    public function destroyTransport(Request $request, Guest $guest, int $id)
    {
        $trans = \App\Models\TransportRequest::where('id', $id)
            ->where('guest_id', $guest->id)
            ->where('source', 'portal')
            ->whereNull('fulfilled_by_id')
            ->firstOrFail();

        $trans->delete();

        return back()->with('success', 'Transport request deleted');
    }

    public function saveCompanions(Request $request, Guest $guest)
    {
        $validated = $request->validate([
            'companions'              => ['required', 'array'],
            'companions.*.name'           => ['required', 'string', 'max:120'],
            'companions.*.relation'       => ['nullable', 'string', 'max:60'],
            'companions.*.passport_no'    => ['nullable', 'string', 'max:40'],
            'companions.*.personal_photo' => ['nullable', 'string', 'max:255'],
            'companions.*.passport_front' => ['nullable', 'string', 'max:255'],
        ]);

        $event = Event::where('active_flag', true)->first();
        if (!$event) return back()->withErrors(['event' => 'No active event found']);

        $companions = collect($validated['companions'])
            ->filter(fn($c) => !empty($c['name']))
            ->map(fn($c) => [
                'name'           => trim($c['name']),
                'relation'       => $c['relation'] ?? 'Companion',
                'passport_no'    => $c['passport_no'] ?? '',
                'personal_photo' => $c['personal_photo'] ?? '',
                'passport_front' => $c['passport_front'] ?? '',
            ])->values()->toArray();

        $guest->events()->updateExistingPivot($event->id, [
            'companions' => $companions,
        ]);

        return back()->with('success', 'Companions updated successfully');
    }
}
