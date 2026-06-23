<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public, guest-facing RSVP controller.
 * Accessed via token from invitation email - no authentication required.
 */
class RsvpController extends Controller
{
    /** GET /rsvp/{token} */
    public function show(string $token): Response
    {
        $invitation = Invitation::with(['guest', 'event', 'matches.venue', 'status'])
            ->where('rsvp_token', $token)
            ->firstOrFail();

        return Inertia::render('Rsvp/Show', [
            'token' => $token,
            'event' => [
                'name'  => $invitation->event->name,
                'venue' => $invitation->event->venue ?? '',
                'dates' => $invitation->event->formatted_dates ?? '',
            ],
            'guest'    => ['name' => $invitation->guest->name],
            'subject'  => $invitation->subject,
            'body'     => $invitation->body,
            'matches'  => $invitation->matches->map(fn ($m) => [
                'id'       => $m->id,
                'stage'    => $m->stage_code ?? $m->stage ?? '',
                'label'    => $m->stage_label ?? $m->name ?? '',
                'homeTeam' => $m->team_a_name,
                'awayTeam' => $m->team_b_name,
                'homeFlag' => $m->team_a_flag ?? '',
                'awayFlag' => $m->team_b_flag ?? '',
                'date'     => optional($m->date)->format('j M Y'),
                'time'     => $m->time ?? '',
                'venue'    => $m->venue?->name ?? '',
                'response' => $m->pivot->response,
            ]),
            'submitted' => $invitation->hasResponded(),
        ]);
    }

    /** POST /rsvp/{token} — record per-match yes/no. */
    public function submit(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('rsvp_token', $token)->firstOrFail();

        $data = $request->validate([
            'responses'   => ['required', 'array', 'min:1'],
            'responses.*' => ['nullable', 'in:yes,no'],
        ]);

        // Update match responses
        foreach ($data['responses'] as $matchId => $response) {
            $invitation->matches()->updateExistingPivot((int) $matchId, [
                'response' => $response,
            ]);
        }

        // Update invitation status based on responses and set responded_at timestamp
        $invitation->update(['responded_at' => now()]);
        $invitation->updateStatusFromResponses();
        
        // Reload invitation to get updated status relationship
        $invitation->load('status');

        // Update guest status based on invitation status
        $invitationStatusName = $invitation->status?->name ?? 'pending';
        $guestStatusName = match($invitationStatusName) {
            'accepted' => 'confirmed',  // Guest accepted = mark guest as confirmed
            'pending' => 'confirmed',   // Partial acceptance still counts as confirmed guest
            'declined' => 'declined',
            default => 'pending',
        };
        
        // Update guest status_id (guest_statuses table)
        $guestStatus = \App\Models\GuestStatus::where('name', $guestStatusName)->first();
        if ($guestStatus && $invitation->guest) {
            $invitation->guest->update([
                'status_id' => $guestStatus->id,
            ]);
        }

        // Update guest_event pivot status (matches invitation status names)
        if ($invitation->event_id && $invitation->guest) {
            $pivotStatus = match($invitationStatusName) {
                'accepted' => 'accepted',
                'pending' => 'pending',  // Partial responses
                'declined' => 'declined',
                default => 'invited',
            };
            
            $invitation->guest->events()->updateExistingPivot($invitation->event_id, [
                'status' => $pivotStatus,
            ]);
        }

        return back()->with('success', 'Your RSVP has been recorded. Thank you!');
    }
}
