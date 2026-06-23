<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\InvitationStatus;
use App\Models\GameMatch;
use App\Models\FlightRequest;
use App\Models\AccommodationRequest;
use App\Services\Gms\GmsMockData;
use App\Mail\GuestInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class GmsInvitationController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        // Roster = guests on this event (via guest_event pivot)
        $roster = [];
        if ($eventId) {
            $roster = Guest::with([
                'status', 
                'group', 
                'events', 
                'invitations' => function ($q) use ($eventId) {
                    $q->where('event_id', $eventId)->with(['matches.venue', 'status']);
                },
                'seats',
                'transportRequests',
                'arrivalDepartureRequests'
            ])
                ->whereHas('events', fn($q) => $q->where('event_id', $eventId))
                ->orderBy('name')
                ->get()
                ->map(function ($guest) use ($eventId) {
                    $pivot = $guest->events->firstWhere('id', $eventId)?->pivot;
                    $latestInvitation = $guest->invitations->first();

                    // Derive invitation status from pivot + invitation data
                    $invStatus = $pivot?->status ?? 'not_invited';

                    // Service statuses
                    $flightRequest = FlightRequest::where('guest_id', $guest->id)
                        ->orderBy('created_at', 'desc')->first();
                    $accommodationRequest = AccommodationRequest::with('status')
                        ->where('guest_id', $guest->id)
                        ->orderBy('created_at', 'desc')->first();

                    // Seat status - check if guest has any seat assignments
                    $seatStatus = null;
                    if ($guest->seats->isNotEmpty()) {
                        $seatStatus = 'assigned';
                    }

                    // Transport status
                    $transportStatus = null;
                    if ($guest->transportRequests->isNotEmpty()) {
                        $latestTransport = $guest->transportRequests->sortByDesc('created_at')->first();
                        $transportStatus = $latestTransport->status?->name 
                            ? strtolower($latestTransport->status->name)
                            : 'pending';
                    }

                    // Arrival & Departure status
                    $adStatus = null;
                    if ($guest->arrivalDepartureRequests->isNotEmpty()) {
                        $latestAD = $guest->arrivalDepartureRequests->sortByDesc('created_at')->first();
                        $adStatus = $latestAD->status ?? 'pending';
                    }

                    $sessions = 0;
                    if ($latestInvitation) {
                        $sessions = $latestInvitation->matches
                            ->filter(fn($m) => $m->pivot->response === 'yes')
                            ->count();
                    }

                    return [
                        'id' => $guest->id,
                        'name' => $guest->name,
                        'email' => $guest->email,
                        'title' => $guest->title,
                        'tier' => $guest->tier,
                        'group' => $guest->group?->name,
                        'group_id' => $guest->group_id,
                        'nationality' => $guest->nationality,
                        'guestType' => $guest->guestType,
                        'reference_number' => $guest->reference_number,
                        'phone' => $guest->phone,
                        'notes' => $guest->notes,
                        'status' => $invStatus,
                        'sessions' => $sessions,
                        'passport' => $guest->qid ? true : false,
                        'invitation' => $latestInvitation ? [
                            'id' => $latestInvitation->id,
                            'status' => $latestInvitation->status?->name ?? 'not_invited',
                            'sent_at' => $latestInvitation->sent_at?->format('Y-m-d H:i:s'),
                            'responded_at' => $latestInvitation->responded_at?->format('Y-m-d H:i:s'),
                            'rsvp_token' => $latestInvitation->rsvp_token,
                            'matches' => $latestInvitation->matches->map(fn($m) => [
                                'id' => $m->id,
                                'stage' => $m->stage_code ?? $m->stage,
                                'homeTeam' => $m->team_a_name,
                                'awayTeam' => $m->team_b_name,
                                'venue' => $m->venue?->name ?? 'TBD',
                                'date' => $m->date,
                                'time' => $m->time,
                                'response' => $m->pivot->response,
                            ])->toArray(),
                        ] : null,
                        'services' => [
                            'flight' => $flightRequest?->status,
                            'accommodation' => $accommodationRequest?->status?->name
                                ? strtolower($accommodationRequest->status->name)
                                : null,
                            'seat' => $seatStatus,
                            'transport' => $transportStatus,
                            'ad' => $adStatus,
                        ],
                    ];
                })
                ->toArray();
        }

        // Directory guests NOT on this event (for Add Guests picker)
        $directory = [];
        if ($eventId) {
            $directory = Guest::with(['group'])
                ->whereDoesntHave('events', fn($q) => $q->where('event_id', $eventId))
                ->orderBy('name')
                ->get()
                ->map(fn($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'group' => $g->group?->name,
                    'nationality' => $g->nationality,
                    'guestType' => $g->guestType,
                    'tier' => $g->tier,
                ])
                ->toArray();
        }

        return Inertia::render('Gms/Invitations/Index', [
            'roster'         => $roster,
            'directory'      => $directory,
            'tiers'          => GmsMockData::getTiers(),
            'emailTemplates' => GmsMockData::getEmailTemplates(),
            'matches'        => GmsMockData::getMatches(),
            'event'          => $event,
        ]);
    }

    /**
     * Add guests from directory to event roster
     */
    public function addGuests(Request $request)
    {
        $validated = $request->validate([
            'guest_ids'   => 'required|array|min:1',
            'guest_ids.*' => 'required|exists:guests,id',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        if (!$eventId) {
            return back()->with('error', 'No active event selected.');
        }

        $added = 0;
        foreach ($validated['guest_ids'] as $guestId) {
            $guest = Guest::find($guestId);
            if ($guest && !$guest->events()->where('event_id', $eventId)->exists()) {
                $guest->events()->attach($eventId, [
                    'status' => 'not_invited',
                    'added_at' => now(),
                ]);
                $added++;
            }
        }

        return back()->with('success', "{$added} guest(s) added to roster.");
    }

    /**
     * Remove a guest from event roster
     */
    public function removeGuest(Request $request, string $guestId)
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        if (!$eventId) {
            return back()->with('error', 'No active event selected.');
        }

        $guest = Guest::findOrFail($guestId);
        $guest->events()->detach($eventId);

        return back()->with('success', "{$guest->name} removed from roster.");
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'guestId'      => 'required|integer|exists:guests,id',
            'subject'      => 'required|string|max:255',
            'body'         => 'required|string',
            'matchIds'     => 'required|array|min:1',
            'matchIds.*'   => 'required|string',
            'rsvpDeadline' => 'nullable|string',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        if (!$eventId) {
            return back()->with('error', 'No active event selected.');
        }

        $guest = Guest::findOrFail($validated['guestId']);

        // Ensure guest is on the event roster; add if not
        if (!$guest->events()->where('event_id', $eventId)->exists()) {
            $guest->events()->attach($eventId, [
                'status' => 'invited',
                'added_at' => now(),
                'invited_at' => now(),
            ]);
        } else {
            // Update pivot status to invited
            $guest->events()->updateExistingPivot($eventId, [
                'status' => 'invited',
                'invited_at' => now(),
            ]);
        }

        $matchIdIntegers = array_map(function($id) {
            return (int) str_replace('M', '', $id);
        }, $validated['matchIds']);

        $matches = GameMatch::with('venue')
            ->whereIn('id', $matchIdIntegers)
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'stageCode' => $match->stage_code ?? $match->stage ?? 'Match',
                    'stageLabel' => $match->stage_label ?? $match->name,
                    'homeTeam' => $match->team_a_name,
                    'awayTeam' => $match->team_b_name,
                    'venueName' => $match->venue?->name ?? '',
                    'date' => $match->date ? \Carbon\Carbon::parse($match->date)->format('M j, Y') : '',
                    'kickoff' => $match->time ?? '',
                ];
            })
            ->toArray();

        $tierName = $guest->tierInfo?->name ?? 'Guest';
        $matchList = collect($matches)->map(function($m) {
            return "• {$m['stageLabel']} — {$m['homeTeam']} vs {$m['awayTeam']} ({$m['date']}, {$m['kickoff']})";
        })->join("\n");

        $replacements = [
            '{{title}}'         => $guest->title ?? '',
            '{{guest_title}}'   => $guest->title ?? '',
            '{{guest_name}}'    => $guest->name,
            '{{tier_name}}'     => $tierName,
            '{{event}}'         => $event['name'] ?? 'Event',
            '{{event_name}}'    => $event['name'] ?? 'Event',
            '{{venue}}'         => $event['venue'] ?? '',
            '{{rsvp_deadline}}' => $validated['rsvpDeadline'] ?? '',
            '{{sender}}'        => auth()->user()?->name ?? 'Event Organizer',
            '{{match_list}}'    => $matchList ?: '(No matches selected)',
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $validated['subject']);
        $body = str_replace(array_keys($replacements), array_values($replacements), $validated['body']);

        $sentStatus = InvitationStatus::where('name', 'sent')->first();

        // Check if invitation already exists for this guest + event
        $invitation = Invitation::where('guest_id', $guest->id)
            ->where('event_id', $eventId)
            ->first();

        if ($invitation) {
            // Update existing invitation
            $invitation->update([
                'subject'   => $subject,
                'body'      => $body,
                'status_id' => $sentStatus?->id,
                'sent_at'   => now(),
            ]);
            
            // Detach old matches and attach new ones
            $invitation->matches()->detach();
            foreach ($matchIdIntegers as $matchId) {
                $invitation->matches()->attach($matchId);
            }
        } else {
            // Create new invitation
            $invitation = Invitation::create([
                'guest_id'  => $guest->id,
                'event_id'  => $eventId,
                'subject'   => $subject,
                'body'      => $body,
                'status_id' => $sentStatus?->id,
                'sent_at'   => now(),
            ]);

            foreach ($matchIdIntegers as $matchId) {
                $invitation->matches()->attach($matchId);
            }
        }

        if ($guest->email) {
            Mail::to($guest->email)->queue(
                new GuestInvitationMail(
                    $guest,
                    $invitation,
                    $matches,
                    $event['name'] ?? 'Event',
                    $event['venue'] ?? ''
                )
            );
        }

        return back()->with('success', 'Invitation sent to ' . $guest->name . '.');
    }

    public function acceptOnBehalf(Request $request, $id)
    {
        $invitation = Invitation::with(['guest', 'matches'])->findOrFail($id);

        foreach ($invitation->matches as $match) {
            $invitation->matches()->updateExistingPivot($match->id, [
                'response' => 'yes',
            ]);
        }

        $invitation->update(['responded_at' => now()]);
        $invitation->updateStatusFromResponses();

        if ($invitation->guest) {
            $confirmedStatus = \App\Models\GuestStatus::where('name', 'confirmed')->first();
            if ($confirmedStatus) {
                $invitation->guest->update([
                    'status_id' => $confirmedStatus->id,
                ]);
            }
            // Update pivot status
            if ($invitation->event_id) {
                $invitation->guest->events()->updateExistingPivot($invitation->event_id, [
                    'status' => 'confirmed',
                ]);
            }
        }

        return back()->with('success', 'Invitation accepted on behalf of ' . $invitation->guest->name . '. All matches accepted.');
    }

    public function markConfirmed(Request $request, $id)
    {
        $invitation = Invitation::with(['guest'])->findOrFail($id);
        $confirmedStatus = InvitationStatus::where('name', 'confirmed')->first();

        $invitation->update([
            'status_id' => $confirmedStatus?->id,
            'responded_at' => $invitation->responded_at ?? now(),
        ]);

        if ($invitation->guest) {
            $confirmedStatus = \App\Models\GuestStatus::where('name', 'confirmed')->first();
            if ($confirmedStatus) {
                $invitation->guest->update([
                    'status_id' => $confirmedStatus->id,
                ]);
            }
            if ($invitation->event_id) {
                $invitation->guest->events()->updateExistingPivot($invitation->event_id, [
                    'status' => 'confirmed',
                ]);
            }
        }

        return back()->with('success', $invitation->guest->name . ' has been marked as confirmed.');
    }

    public function markDeclined(Request $request, $id)
    {
        $invitation = Invitation::with(['guest'])->findOrFail($id);
        $declinedStatus = InvitationStatus::where('name', 'declined')->first();

        $invitation->update([
            'status_id' => $declinedStatus?->id,
            'responded_at' => $invitation->responded_at ?? now(),
        ]);

        if ($invitation->guest) {
            $declinedStatus = \App\Models\GuestStatus::where('name', 'declined')->first();
            if ($declinedStatus) {
                $invitation->guest->update([
                    'status_id' => $declinedStatus->id,
                ]);
            }
            if ($invitation->event_id) {
                $invitation->guest->events()->updateExistingPivot($invitation->event_id, [
                    'status' => 'declined',
                ]);
            }
        }

        return back()->with('success', $invitation->guest->name . ' has been marked as declined.');
    }

    public function resetToPending(Request $request, $id)
    {
        $invitation = Invitation::with(['guest', 'matches'])->findOrFail($id);
        $notInvitedStatus = InvitationStatus::where('name', 'not_invited')->first();

        foreach ($invitation->matches as $match) {
            $invitation->matches()->updateExistingPivot($match->id, [
                'response' => null,
            ]);
        }

        $invitation->update([
            'status_id' => $notInvitedStatus?->id,
            'responded_at' => null,
        ]);

        if ($invitation->guest) {
            $pendingStatus = \App\Models\GuestStatus::where('name', 'pending')->first();
            if ($pendingStatus) {
                $invitation->guest->update([
                    'status_id' => $pendingStatus->id,
                ]);
            }
            if ($invitation->event_id) {
                $invitation->guest->events()->updateExistingPivot($invitation->event_id, [
                    'status' => 'not_invited',
                ]);
            }
        }

        return back()->with('success', $invitation->guest->name . ' has been reset to pending.');
    }
}
