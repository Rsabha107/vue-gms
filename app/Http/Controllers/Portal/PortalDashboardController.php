<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Event;
use App\Services\Gms\PortalTokenService;
use Illuminate\Http\Request;

class PortalDashboardController extends Controller
{
    /**
     * Display the guest portal dashboard
     */
    public function show(Request $request, Guest $guest)
    {
        // Verify signed URL
        if (!$request->hasValidSignature()) {
            abort(403, 'This portal link has expired or is invalid.');
        }

        // Get current event
        $event = Event::where('active_flag', true)->first();
        
        if (!$event) {
            abort(404, 'No active event found.');
        }

        // Get guest's invitation for this event
        $invitation = $guest->invitations()
            ->where('event_id', $event->id)
            ->with('status')
            ->first();

        if (!$invitation) {
            abort(404, 'No invitation found for this guest.');
        }

        // Track portal access
        PortalTokenService::trackPortalAccess($invitation);

        // Load guest relationships
        $guest->load(['tierInfo', 'group']);

        // Get service requests for this guest
        $flightRequests = $guest->flightRequests()
            ->where('event_id', $event->id)
            ->with('legs')
            ->get();

        $accommodationRequests = $guest->accommodationRequests()
            ->where('event_id', $event->id)
            ->with('status')
            ->get();

        $transportRequests = $guest->transportRequests()
            ->where('event_id', $event->id)
            ->with('status')
            ->get();

        $adRequests = $guest->arrivalDepartureRequests()->get();

        // Get match invitations
        $matches = $invitation->matches()->with('venue')->get();

        return inertia('Portal/Dashboard', [
            'guest' => $guest,
            'event' => $event,
            'invitation' => $invitation,
            'matches' => $matches,
            'services' => [
                'flights' => $flightRequests,
                'accommodation' => $accommodationRequests,
                'transport' => $transportRequests,
                'arrivalDeparture' => $adRequests,
            ],
        ]);
    }
}

