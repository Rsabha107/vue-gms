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

        // Check TOTP verification (if portal auth mode requires it)
        if ($event->portal_auth_mode === 'totp' || $event->portal_auth_mode === 'full_auth') {
            if (!$request->session()->get("portal_totp_verified_{$guest->id}")) {
                return redirect()->to(
                    \Illuminate\Support\Facades\URL::signedRoute('portal.totp.show', ['guest' => $guest->id])
                );
            }
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
            ->with(['legs', 'status'])
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

        // Get companions from guest_event pivot
        $pivot = $guest->events()->where('event_id', $event->id)->first()?->pivot;
        $companions = $pivot?->companions ?? [];

        return inertia('Portal/Dashboard', [
            'guest' => $guest,
            'event' => $event,
            'invitation' => $invitation,
            'matches' => $matches,
            'companions' => $companions,
            'services' => [
                'flights' => $flightRequests,
                'accommodation' => $accommodationRequests,
                'transport' => $transportRequests,
                'arrivalDeparture' => $adRequests,
            ],
        ]);
    }
}

