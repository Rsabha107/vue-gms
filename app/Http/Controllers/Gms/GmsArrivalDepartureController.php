<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\ArrivalDepartureRequest;
use App\Models\Guest;
use App\Models\ServiceLevel;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsArrivalDepartureController extends Controller
{
    public function index()
    {
        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        return Inertia::render('Gms/ArrivalDeparture/Index', [
            'requests' => GmsMockData::getArrivalDepartureRequests(),
            'guests'   => Guest::where('guestType', 'international')
                ->when($eventId, fn($q) => $q->where('event_id', $eventId))
                ->with(['tierInfo', 'invitation' => function($query) use ($eventId) {
                    $query->when($eventId, fn($q) => $q->where('event_id', $eventId));
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
                        'invitationStatus' => $guest->invitation?->status ?? null,
                        'hasConfirmedInvitation' => $guest->invitation?->status === 'confirmed',
                    ];
                }),
            'tiers'    => ServiceLevel::all(),
            'event'    => $event,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guestId'  => 'required|exists:guests,id',
            'type'     => 'required|in:arrival,departure',
            'flightNo' => 'required|string|max:20',
            'terminal' => 'required|string|max:60',
            'datetime' => 'required|date',
            'lounge'   => 'nullable|string|max:100',
            'greeter'  => 'nullable|string|max:100',
            'notes'    => 'nullable|string',
        ]);

        ArrivalDepartureRequest::create([
            'guest_id' => $validated['guestId'],
            'type' => $validated['type'],
            'flight_no' => $validated['flightNo'],
            'terminal' => $validated['terminal'],
            'datetime' => $validated['datetime'],
            'lounge' => $validated['lounge'] ?? null,
            'greeter' => $validated['greeter'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'A&D request created.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled'
        ]);

        $adRequest = ArrivalDepartureRequest::findOrFail($id);
        $adRequest->update(['status' => $validated['status']]);

        return back()->with('success', 'Status updated.');
    }

    public function destroy(string $id)
    {
        $adRequest = ArrivalDepartureRequest::findOrFail($id);
        $adRequest->delete();

        return back()->with('success', 'Request deleted.');
    }
}
