<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\TransportRequest;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsTransportController extends Controller
{
    public function index()
    {
        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        // Fetch transport requests from database
        $requests = TransportRequest::where('event_id', $eventId)
            ->with(['guest', 'status'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($r) {
                return [
                    'id' => $r->code,
                    'guestId' => $r->guest_id,
                    'guestName' => $r->guest->name ?? 'Unknown',
                    'status' => $r->status_id,
                    'type' => $r->type,
                    'vehicle' => $r->vehicle,
                    'pickupLocation' => $r->pickup_location,
                    'dropoffLocation' => $r->dropoff_location,
                    'datetime' => $r->datetime,
                    'driver' => $r->driver,
                    'notes' => $r->notes,
                ];
            });

        return Inertia::render('Gms/Transport/Index', [
            'requests' => $requests,
            'guests'   => Guest::where('guestType', 'international')
                ->when($eventId, fn($q) => $q->where('event_id', $eventId))
                ->with(['tierInfo', 'group'])
                ->orderBy('name')
                ->get()
                ->map(function($g) {
                    return [
                        'id' => $g->id,
                        'name' => $g->name,
                        'tier' => $g->tier,
                        'guestType' => $g->guestType,
                        'group' => $g->group->name ?? null,
                        'host' => $g->host,
                        'email' => $g->email,
                    ];
                }),
            'tiers'    => GmsMockData::getTiers(),
            'event'    => $event,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guestId'        => 'required|exists:guests,id',
            'type'           => 'required|string|max:60',
            'vehicle'        => 'nullable|string|max:80',
            'pickupLocation' => 'required|string|max:120',
            'dropoffLocation'=> 'required|string|max:120',
            'datetime'       => 'required|string',
            'notes'          => 'nullable|string',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? 1;

        // Generate unique code
        $lastCode = TransportRequest::where('event_id', $eventId)
            ->orderBy('id', 'desc')
            ->value('code');
        $nextNumber = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 1;
        $code = 'TRN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        TransportRequest::create([
            'event_id' => $eventId,
            'guest_id' => $validated['guestId'],
            'code' => $code,
            'status_id' => 'pending',
            'type' => $validated['type'],
            'vehicle' => $validated['vehicle'],
            'pickup_location' => $validated['pickupLocation'],
            'dropoff_location' => $validated['dropoffLocation'],
            'datetime' => $validated['datetime'],
            'driver' => 'TBD',
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Transport request created.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $request->validate(['status' => 'required|in:pending,confirmed,cancelled']);
        
        $transportRequest = TransportRequest::where('code', $id)->firstOrFail();
        $transportRequest->update(['status_id' => $request->status]);
        
        return back()->with('success', 'Status updated.');
    }

    public function destroy(string $id)
    {
        $transportRequest = TransportRequest::where('code', $id)->firstOrFail();
        $transportRequest->delete();
        
        return back()->with('success', 'Transport request deleted.');
    }
}
