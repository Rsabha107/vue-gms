<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\FlightRequest;
use App\Models\FlightLeg;
use App\Models\Guest;
use App\Models\ServiceLevel;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsFlightController extends Controller
{
    public function index()
    {
        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        // Fetch flight requests with relationships, filtered by event
        $flightRequests = FlightRequest::with(['guest', 'legs'])
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($fr) {
                // Transform to match Vue component's expected structure
                $inbound = $fr->legs->where('dir', 'Inbound')->first();
                $outbound = $fr->legs->where('dir', 'Outbound')->first();
                
                return [
                    'id' => $fr->code,
                    'guestId' => $fr->guest_id,
                    'guestName' => $fr->guest->name ?? '',
                    'status' => $fr->status,
                    'changeRequest' => $fr->status === 'change',
                    'pnr' => $fr->ref,
                    'pax' => $fr->pax,
                    'submitted' => $fr->requested_at ?? $fr->created_at->format('Y-m-d H:i'),
                    'notes' => $fr->note ?? '',
                    
                    // Main flight info (from inbound leg)
                    'flightNo' => $inbound ? $inbound->flight_no : '',
                    'airline' => $inbound ? $inbound->airline : 'Qatar Airways',
                    'route' => $inbound && $outbound 
                        ? "{$inbound->from_code} → {$inbound->to_code}" 
                        : '',
                    'class' => $inbound ? $inbound->cls : 'Business',
                    
                    // Inbound leg details
                    'inboundFlight' => $inbound ? $inbound->flight_no : '',
                    'origin' => $inbound ? $inbound->from_code : '',
                    'originCity' => $inbound ? $inbound->from_city : '',
                    'destination' => $inbound ? $inbound->to_code : '',
                    'destCity' => $inbound ? $inbound->to_city : '',
                    'date' => $inbound ? $inbound->date : '',
                    'time' => $inbound ? $inbound->dep : '',
                    'arrival' => $inbound ? $inbound->date : '',
                    'arrivalTime' => $inbound ? $inbound->arr : '',
                    'duration' => $inbound ? $inbound->dur : '',
                    'inboundTerminal' => 'Arrival — Hamad International (HIA)',
                    
                    // Outbound leg details
                    'outboundFlight' => $outbound ? $outbound->flight_no : '',
                    'outboundDate' => $outbound ? $outbound->date : '',
                    'outboundTime' => $outbound ? $outbound->dep : '',
                    'outboundArrival' => $outbound ? $outbound->date : '',
                    'outboundArrivalTime' => $outbound ? $outbound->arr : '',
                    'outboundTerminal' => 'Departure — Hamad International (HIA)',
                    
                    // Raw legs for leg editor
                    'legs' => $fr->legs->map(function ($leg) {
                        return [
                            'id' => $leg->id,
                            'dir' => $leg->dir,
                            'airline' => $leg->airline,
                            'flightNo' => $leg->flight_no,
                            'fromCode' => $leg->from_code,
                            'fromCity' => $leg->from_city,
                            'toCode' => $leg->to_code,
                            'toCity' => $leg->to_city,
                            'date' => $leg->date,
                            'dep' => $leg->dep,
                            'arr' => $leg->arr,
                            'cls' => $leg->cls,
                            'dur' => $leg->dur,
                            'sort' => $leg->sort,
                        ];
                    })->toArray(),
                ];
            });

        return Inertia::render('Gms/Flights/Index', [
            'requests' => $flightRequests,
            'guests'   => Guest::where('guestType', 'international')
                ->when($eventId, fn($q) => $q->where('event_id', $eventId))
                ->with(['tierInfo', 'invitation' => function($query) use ($eventId) {
                    $query->when($eventId, fn($q) => $q->where('event_id', $eventId))
                        ->with('status');
                }])
                ->orderBy('name')
                ->get()
                ->map(function($guest) {
                    return [
                        'id' => $guest->id,
                        'name' => $guest->name,
                        'tier' => $guest->tier,
                        'guestType' => $guest->guestType,
                        'nationality' => $guest->nationality,
                        'invitationStatus' => $guest->invitation?->status?->name ?? null,
                        'hasConfirmedInvitation' => $guest->invitation?->status?->name === 'confirmed',
                    ];
                }),
            'tiers'    => ServiceLevel::all(),
            'event'    => $event,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guestId'     => 'required|exists:guests,id',
            'class'       => 'required|string',
            'pax'         => 'required|integer|min:1',
            'origin'      => 'required|string|max:3',
            'destination' => 'required|string|max:3',
            // Inbound leg
            'inboundFlightNo' => 'required|string|max:20',
            'date'        => 'required|date',
            'inboundDepTime' => 'required|string|max:10',
            'inboundArrTime' => 'required|string|max:10',
            'inboundDuration' => 'nullable|string|max:20',
            // Outbound leg
            'outboundFlightNo' => 'required|string|max:20',
            'outboundDate' => 'required|date',
            'outboundDepTime' => 'required|string|max:10',
            'outboundArrTime' => 'required|string|max:10',
            'outboundDuration' => 'nullable|string|max:20',
            // Optional
            'isChange'    => 'sometimes|boolean',
        ]);

        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        // Generate code
        $lastFlight = FlightRequest::when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('id', 'desc')
            ->first();
        $nextNum = $lastFlight ? (intval(substr($lastFlight->code, 3)) + 1) : 1;
        $code = 'FL-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        // Determine status based on isChange flag
        $status = ($validated['isChange'] ?? false) ? 'change' : 'new';

        $flightRequest = FlightRequest::create([
            'event_id' => $eventId,
            'guest_id' => $validated['guestId'],
            'code' => $code,
            'status' => $status,
            'pax' => $validated['pax'],
            'ref' => strtoupper(substr(md5(uniqid()), 0, 6)), // Generate PNR
        ]);

        // Create inbound leg with user-provided data
        $flightRequest->legs()->create([
            'dir' => 'Inbound',
            'airline' => 'Qatar Airways',
            'flight_no' => $validated['inboundFlightNo'],
            'from_code' => strtoupper($validated['destination']),
            'from_city' => strtoupper($validated['destination']),
            'to_code' => strtoupper($validated['origin']),
            'to_city' => 'Doha',
            'date' => $validated['date'],
            'dep' => $validated['inboundDepTime'],
            'arr' => $validated['inboundArrTime'],
            'cls' => $validated['class'],
            'dur' => $validated['inboundDuration'] ?? '',
            'sort' => 0,
        ]);

        // Create outbound leg with user-provided data
        $flightRequest->legs()->create([
            'dir' => 'Outbound',
            'airline' => 'Qatar Airways',
            'flight_no' => $validated['outboundFlightNo'],
            'from_code' => strtoupper($validated['origin']),
            'from_city' => 'Doha',
            'to_code' => strtoupper($validated['destination']),
            'to_city' => strtoupper($validated['destination']),
            'date' => $validated['outboundDate'],
            'dep' => $validated['outboundDepTime'],
            'arr' => $validated['outboundArrTime'],
            'cls' => $validated['class'],
            'dur' => $validated['outboundDuration'] ?? '',
            'sort' => 1,
        ]);

        return back()->with('success', 'Flight request created.');
    }

    public function update(Request $request, string $id)
    {
        $flightRequest = FlightRequest::where('code', $id)->firstOrFail();
        
        $validated = $request->validate([
            'pax' => 'sometimes|integer|min:1',
            'ref' => 'sometimes|string|max:12',
            'note' => 'sometimes|string|nullable',
        ]);

        $flightRequest->update($validated);

        return back()->with('success', 'Flight updated.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,confirmed,change,cancelled'
        ]);

        $flightRequest = FlightRequest::where('code', $id)->firstOrFail();
        $flightRequest->update(['status' => $validated['status']]);

        return back()->with('success', 'Status updated.');
    }

    public function updateLeg(Request $request, string $id, string $legId)
    {
        $validated = $request->validate([
            'airline' => 'nullable|string|max:100',
            'flight_no' => 'nullable|string|max:12',
            'from_code' => 'nullable|string|max:8',
            'from_city' => 'nullable|string|max:100',
            'to_code' => 'nullable|string|max:8',
            'to_city' => 'nullable|string|max:100',
            'date' => 'nullable|string|max:16',
            'dep' => 'nullable|string|max:12',
            'arr' => 'nullable|string|max:12',
            'cls' => 'nullable|string|max:16',
            'dur' => 'nullable|string|max:16',
        ]);

        $flightRequest = FlightRequest::where('code', $id)->firstOrFail();
        $leg = FlightLeg::where('id', $legId)
            ->where('flight_request_id', $flightRequest->id)
            ->firstOrFail();
        
        $leg->update($validated);

        return back()->with('success', 'Flight leg updated.');
    }

    public function destroy(string $id)
    {
        $flightRequest = FlightRequest::where('code', $id)->firstOrFail();
        $flightRequest->delete();

        return back()->with('success', 'Flight request deleted.');
    }
}
