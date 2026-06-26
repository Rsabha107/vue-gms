<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\FlightRequest;
use App\Models\FlightLeg;
use App\Models\Guest;
use App\Models\ServiceLevel;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GmsFlightController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        $allFlights = FlightRequest::with(['guest', 'legs', 'fulfillsRequest', 'status'])
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('created_at', 'desc')
            ->get();

        // Collect all unique airport codes from flight legs
        $airportCodes = $allFlights->flatMap(fn($fr) => $fr->legs->pluck('from_code'))
            ->merge($allFlights->flatMap(fn($fr) => $fr->legs->pluck('to_code')))
            ->filter()
            ->unique()
            ->values();

        // Fetch all airport cities in one query
        $airports = \App\Models\Airport::whereIn('iata_code', $airportCodes)
            ->get(['iata_code', 'municipality'])
            ->keyBy('iata_code')
            ->map(fn($a) => $a->municipality);

        $transform = function ($fr) use ($airports) {
            $inbound = $fr->legs->where('dir', 'Inbound')->first();
            $outbound = $fr->legs->where('dir', 'Outbound')->first();
            $statusName = $fr->status->name ?? 'new';

            // Inbound: FROM guest's city (destination) TO event city (origin)
            // Outbound: FROM event city (origin) TO guest's city (destination)
            // So we use outbound leg for intuitive origin/destination mapping
            $originCode = $outbound ? $outbound->from_code : ($inbound ? $inbound->to_code : '');
            $destCode = $outbound ? $outbound->to_code : ($inbound ? $inbound->from_code : '');

            // Get cities from airports table
            $originCity = $originCode ? ($airports[$originCode] ?? $originCode) : '';
            $destCity = $destCode ? ($airports[$destCode] ?? $destCode) : '';

            return [
                'id' => $fr->code,
                'guestId' => $fr->guest_id,
                'guestName' => $fr->guest->name ?? '',
                'status' => $statusName,
                'changeRequest' => $statusName === 'change',
                'pnr' => $fr->ref,
                'pax' => $fr->pax,
                'submitted' => $fr->requested_at ?? $fr->created_at->format('Y-m-d H:i'),
                'notes' => $fr->note ?? '',
                'source' => $fr->source,
                'initiatedBy' => $fr->initiated_by,
                'fulfilledById' => $fr->fulfilled_by_id,
                'fulfillsRequestId' => $fr->fulfills_request_id,
                'fulfillsRequestCode' => $fr->fulfillsRequest?->code,

                'flightNo' => $inbound ? $inbound->flight_no : '',
                'airline' => $inbound ? $inbound->airline : 'Qatar Airways',
                'route' => $inbound && $outbound
                    ? "{$destCode} ⇄ {$originCode}"
                    : '',
                'class' => $inbound ? $inbound->cls : 'Business',

                'inboundFlight' => $inbound ? $inbound->flight_no : '',
                'origin' => $originCode,
                'originCity' => $originCity,
                'destination' => $destCode,
                'destCity' => $destCity,
                'date' => $inbound ? $inbound->date : '',
                'time' => $inbound ? $inbound->dep : '',
                'arrival' => $inbound ? $inbound->date : '',
                'arrivalTime' => $inbound ? $inbound->arr : '',
                'duration' => $inbound ? $inbound->dur : '',
                'inboundTerminal' => 'Arrival — Hamad International (HIA)',

                'outboundFlight' => $outbound ? $outbound->flight_no : '',
                'outboundDate' => $outbound ? $outbound->date : '',
                'outboundTime' => $outbound ? $outbound->dep : '',
                'outboundArrival' => $outbound ? $outbound->date : '',
                'outboundArrivalTime' => $outbound ? $outbound->arr : '',
                'outboundTerminal' => 'Departure — Hamad International (HIA)',

                'legs' => $fr->legs->map(function ($leg) use ($airports) {
                    // For portal guest requests, from_code/to_code may be 'XXX' with actual city in from_city/to_city
                    // Only use airport lookup if we don't have a proper city name stored
                    $fromCity = $leg->from_city;
                    if (!$fromCity || $fromCity === $leg->from_code || $fromCity === 'XXX') {
                        $fromCity = $airports[$leg->from_code] ?? $leg->from_code;
                    }
                    
                    $toCity = $leg->to_city;
                    if (!$toCity || $toCity === $leg->to_code || $toCity === 'XXX') {
                        $toCity = $airports[$leg->to_code] ?? $leg->to_code;
                    }
                    
                    return [
                        'id' => $leg->id,
                        'dir' => $leg->dir,
                        'airline' => $leg->airline,
                        'flightNo' => $leg->flight_no,
                        'fromCode' => $leg->from_code,
                        'fromCity' => $fromCity,
                        'toCode' => $leg->to_code,
                        'toCity' => $toCity,
                        'date' => $leg->date,
                        'dep' => $leg->dep,
                        'arr' => $leg->arr,
                        'cls' => $leg->cls,
                        'dur' => $leg->dur,
                        'sort' => $leg->sort,
                    ];
                })->toArray(),
            ];
        };

        $guestRequests = $allFlights
            ->filter(fn($fr) => $fr->source === 'portal' && $fr->initiated_by === 'guest')
            ->map($transform)
            ->values();

        $regularRequests = $allFlights
            ->filter(fn($fr) => !($fr->source === 'portal' && $fr->initiated_by === 'guest'))
            ->map($transform)
            ->values();

        return Inertia::render('Gms/Flights/Index', [
            'requests'      => $regularRequests,
            'guestRequests' => $guestRequests,
            'guests'        => Guest::where('guestType', 'international')
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
            'tiers'  => ServiceLevel::all(),
            'event'  => $event,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guestId'     => 'required|exists:guests,id',
            'class'       => 'required|string',
            'pax'         => 'required|integer|min:1',
            'pnr'         => 'nullable|string|max:12',
            'origin'      => 'required|string|max:3',
            'destination' => 'required|string|max:3',
            'destinationCity' => 'nullable|string|max:100',
            'inboundFlightNo' => 'required|string|max:20',
            'date'        => 'required|date',
            'inboundDepTime' => 'required|string|max:10',
            'inboundArrTime' => 'required|string|max:10',
            'inboundDuration' => 'nullable|string|max:20',
            'outboundFlightNo' => 'required|string|max:20',
            'outboundDate' => 'required|date',
            'outboundDepTime' => 'required|string|max:10',
            'outboundArrTime' => 'required|string|max:10',
            'outboundDuration' => 'nullable|string|max:20',
            'isChange'    => 'sometimes|boolean',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        $code = $this->nextCode($eventId);
        // Manual flight creation by team should be 'confirmed' by default
        $statusName = ($validated['isChange'] ?? false) ? 'change' : 'confirmed';
        $status = \App\Models\InvitationStatus::where('name', $statusName)->first();
        
        if (!$status) {
            \Log::error("Status '{$statusName}' not found in invitation_statuses table");
            return back()->withErrors(['status' => "Status '{$statusName}' not found. Please contact administrator."]);
        }

        $flightRequest = FlightRequest::create([
            'event_id' => $eventId,
            'guest_id' => $validated['guestId'],
            'code' => $code,
            'status_id' => $status->id,
            'pax' => $validated['pax'],
            'ref' => !empty($validated['pnr']) ? strtoupper($validated['pnr']) : strtoupper(substr(md5(uniqid()), 0, 6)),
            'source' => 'manual',
            'initiated_by' => 'team',
        ]);

        $this->createLegs($flightRequest, $validated);

        return back()->with('success', 'Flight request created.');
    }

    public function bookGuestRequest(Request $request, string $guestRequestCode)
    {
        $guestRequest = FlightRequest::where('code', $guestRequestCode)->firstOrFail();

        if ($guestRequest->source !== 'portal' || $guestRequest->fulfilled_by_id !== null) {
            abort(422, 'This request has already been fulfilled.');
        }

        $validated = $request->validate([
            'guestId'     => 'required|exists:guests,id',
            'class'       => 'required|string',
            'pax'         => 'required|integer|min:1',
            'pnr'         => 'nullable|string|max:12',
            'origin'      => 'required|string|max:3',
            'destination' => 'required|string|max:3',
            'destinationCity' => 'nullable|string|max:100',
            'inboundFlightNo' => 'required|string|max:20',
            'date'        => 'required|date',
            'inboundDepTime' => 'required|string|max:10',
            'inboundArrTime' => 'required|string|max:10',
            'inboundDuration' => 'nullable|string|max:20',
            'outboundFlightNo' => 'required|string|max:20',
            'outboundDate' => 'required|date',
            'outboundDepTime' => 'required|string|max:10',
            'outboundArrTime' => 'required|string|max:10',
            'outboundDuration' => 'nullable|string|max:20',
        ]);

        $code = $this->nextCode($guestRequest->event_id);

        // When team books a guest request, it should be confirmed immediately
        $status = \App\Models\InvitationStatus::where('name', 'confirmed')->first();
        
        if (!$status) {
            \Log::error("Status 'confirmed' not found in invitation_statuses table");
            return back()->withErrors(['status' => "Status 'confirmed' not found. Please contact administrator."]);
        }

        $booking = FlightRequest::create([
            'event_id' => $guestRequest->event_id,
            'guest_id' => $validated['guestId'],
            'code' => $code,
            'status_id' => $status->id,
            'pax' => $validated['pax'],
            'ref' => !empty($validated['pnr']) ? strtoupper($validated['pnr']) : strtoupper(substr(md5(uniqid()), 0, 6)),
            'source' => 'manual',
            'initiated_by' => 'team',
            'fulfills_request_id' => $guestRequest->id,
        ]);

        $this->createLegs($booking, $validated);

        $guestRequest->update(['fulfilled_by_id' => $booking->id]);

        return back()->with('success', 'Flight booked from guest request.');
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

    public function updateFull(Request $request, string $id)
    {
        $flightRequest = FlightRequest::where('code', $id)->with('legs')->firstOrFail();

        $validated = $request->validate([
            'class'       => 'required|string',
            'pax'         => 'required|integer|min:1',
            'pnr'         => 'nullable|string|max:12',
            'origin'      => 'required|string|max:3',
            'destination' => 'required|string|max:3',
            'inboundFlightNo' => 'required|string|max:20',
            'date'        => 'required|date',
            'inboundDepTime' => 'required|string|max:10',
            'inboundArrTime' => 'required|string|max:10',
            'inboundDuration' => 'nullable|string|max:20',
            'outboundFlightNo' => 'required|string|max:20',
            'outboundDate' => 'required|date',
            'outboundDepTime' => 'required|string|max:10',
            'outboundArrTime' => 'required|string|max:10',
            'outboundDuration' => 'nullable|string|max:20',
            'isChange'    => 'sometimes|boolean',
        ]);

        // Update flight request basic info
        $statusName = ($validated['isChange'] ?? false) ? 'change' : $flightRequest->status->name;
        $statusId = \App\Models\InvitationStatus::where('name', $statusName)->value('id');
        
        $flightRequest->update([
            'pax' => $validated['pax'],
            'ref' => !empty($validated['pnr']) ? strtoupper($validated['pnr']) : $flightRequest->ref,
            'status_id' => $statusId,
        ]);

        // Update or create inbound leg
        $inboundLeg = $flightRequest->legs->where('dir', 'Inbound')->first();
        $inboundData = [
            'dir' => 'Inbound',
            'airline' => 'Qatar Airways',
            'flight_no' => $validated['inboundFlightNo'],
            'from_code' => $validated['destination'],
            'from_city' => null,
            'to_code' => $validated['origin'],
            'to_city' => null,
            'date' => $validated['date'],
            'dep' => $validated['inboundDepTime'],
            'arr' => $validated['inboundArrTime'],
            'cls' => $validated['class'],
            'dur' => $validated['inboundDuration'],
            'sort' => 0,
        ];

        if ($inboundLeg) {
            $inboundLeg->update($inboundData);
        } else {
            $flightRequest->legs()->create($inboundData);
        }

        // Update or create outbound leg
        $outboundLeg = $flightRequest->legs->where('dir', 'Outbound')->first();
        $outboundData = [
            'dir' => 'Outbound',
            'airline' => 'Qatar Airways',
            'flight_no' => $validated['outboundFlightNo'],
            'from_code' => $validated['origin'],
            'from_city' => null,
            'to_code' => $validated['destination'],
            'to_city' => null,
            'date' => $validated['outboundDate'],
            'dep' => $validated['outboundDepTime'],
            'arr' => $validated['outboundArrTime'],
            'cls' => $validated['class'],
            'dur' => $validated['outboundDuration'],
            'sort' => 1,
        ];

        if ($outboundLeg) {
            $outboundLeg->update($outboundData);
        } else {
            $flightRequest->legs()->create($outboundData);
        }

        return back()->with('success', 'Flight request updated.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,confirmed,change,cancelled'
        ]);

        $statusId = \App\Models\InvitationStatus::where('name', $validated['status'])->value('id');
        $flightRequest = FlightRequest::with(['guest', 'legs'])->where('code', $id)->firstOrFail();
        $flightRequest->update(['status_id' => $statusId]);

        if ($validated['status'] === 'confirmed' && $flightRequest->guest?->email) {
            $inbound = $flightRequest->legs->where('dir', 'Inbound')->first();
            $outbound = $flightRequest->legs->where('dir', 'Outbound')->first();
            $event = GmsMockData::getEvent();
            \App\Services\Gms\ServiceConfirmationService::sendFlightConfirmation($flightRequest->guest, $event['name'] ?? '', [
                'code'     => $flightRequest->ref ?? $flightRequest->code,
                'route'    => ($inbound?->from_code ?? '') . ' → ' . ($inbound?->to_code ?? ''),
                'class'    => $inbound?->cls ?? '',
                'pax'      => $flightRequest->pax,
                'inbound'  => ($inbound?->date ?? '') . ' · ' . ($inbound?->dep ?? ''),
                'outbound' => ($outbound?->date ?? '') . ' · ' . ($outbound?->dep ?? ''),
            ]);
        }

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

    public function confirmBoarding(string $id)
    {
        $flightRequest = FlightRequest::where('code', $id)->firstOrFail();
        $flightRequest->update(['boarded_at' => now()]);
        return back()->with('success', 'Boarding confirmed.');
    }

    public function destroy(string $id)
    {
        $flightRequest = FlightRequest::where('code', $id)->firstOrFail();
        $flightRequest->delete();

        return back()->with('success', 'Flight request deleted.');
    }

    private function nextCode(?int $eventId): string
    {
        $lastFlight = FlightRequest::when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('id', 'desc')
            ->first();
        $nextNum = $lastFlight ? (intval(substr($lastFlight->code, 3)) + 1) : 1;
        return 'FL-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }

    private function createLegs(FlightRequest $flightRequest, array $validated): void
    {
        // Use destinationCity if provided (for portal guest requests), otherwise lookup from airports table
        $destinationCity = $validated['destinationCity'] ?? null;
        
        $flightRequest->legs()->create([
            'dir' => 'Inbound',
            'airline' => 'Qatar Airways',
            'flight_no' => $validated['inboundFlightNo'],
            'from_code' => strtoupper($validated['destination']),
            'from_city' => $destinationCity,
            'to_code' => strtoupper($validated['origin']),
            'to_city' => 'Doha',
            'date' => $validated['date'],
            'dep' => $validated['inboundDepTime'],
            'arr' => $validated['inboundArrTime'],
            'cls' => $validated['class'],
            'dur' => $validated['inboundDuration'] ?? '',
            'sort' => 0,
        ]);

        $flightRequest->legs()->create([
            'dir' => 'Outbound',
            'airline' => 'Qatar Airways',
            'flight_no' => $validated['outboundFlightNo'],
            'from_code' => strtoupper($validated['origin']),
            'from_city' => 'Doha',
            'to_code' => strtoupper($validated['destination']),
            'to_city' => $destinationCity,
            'date' => $validated['outboundDate'],
            'dep' => $validated['outboundDepTime'],
            'arr' => $validated['outboundArrTime'],
            'cls' => $validated['class'],
            'dur' => $validated['outboundDuration'] ?? '',
            'sort' => 1,
        ]);
    }
}
