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
                ->with('tierInfo')
                ->orderBy('name')
                ->get(['id', 'name', 'tier', 'guestType', 'nationality']),
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
            'date'        => 'required|date',
            'outboundDate'=> 'required|date',
            'origin'      => 'required|string|max:3',
            'destination' => 'required|string|max:3',
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

        // Create inbound leg
        $flightRequest->legs()->create([
            'dir' => 'Inbound',
            'airline' => 'Qatar Airways',
            'flight_no' => 'QR' . rand(100, 999),
            'from_code' => strtoupper($validated['destination']),
            'from_city' => strtoupper($validated['destination']),
            'to_code' => strtoupper($validated['origin']),
            'to_city' => 'Doha',
            'date' => $validated['date'],
            'dep' => '08:00',
            'arr' => '12:00',
            'cls' => $validated['class'],
            'dur' => '4h 00m',
            'sort' => 0,
        ]);

        // Create outbound leg
        $flightRequest->legs()->create([
            'dir' => 'Outbound',
            'airline' => 'Qatar Airways',
            'flight_no' => 'QR' . rand(100, 999),
            'from_code' => strtoupper($validated['origin']),
            'from_city' => 'Doha',
            'to_code' => strtoupper($validated['destination']),
            'to_city' => strtoupper($validated['destination']),
            'date' => $validated['outboundDate'],
            'dep' => '14:00',
            'arr' => '18:00',
            'cls' => $validated['class'],
            'dur' => '4h 00m',
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
            'airline' => 'sometimes|string|max:100',
            'flight_no' => 'sometimes|string|max:12',
            'from_code' => 'sometimes|string|max:8',
            'from_city' => 'sometimes|string|max:100',
            'to_code' => 'sometimes|string|max:8',
            'to_city' => 'sometimes|string|max:100',
            'date' => 'sometimes|string|max:16',
            'dep' => 'sometimes|string|max:12',
            'arr' => 'sometimes|string|max:12',
            'cls' => 'sometimes|string|max:16',
            'dur' => 'sometimes|string|max:16',
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
