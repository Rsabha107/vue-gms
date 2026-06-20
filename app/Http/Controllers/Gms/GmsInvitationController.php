<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Invitation;
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

        // Fetch invitations for the current event with relationships
        // Group by guest_id to show only the most recent invitation per guest
        $invitations = Invitation::with(['guest.status', 'guest.group', 'matches.venue'])
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('guest_id')
            ->map(fn($group) => $group->first()) // Take the most recent invitation per guest
            ->values()
            ->map(function ($invitation) {
                // Get service statuses for this guest
                $guestId = $invitation->guest_id;
                
                // Flight status
                $flightRequest = FlightRequest::where('guest_id', $guestId)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $flightStatus = $flightRequest ? $flightRequest->status : null;
                
                // Accommodation status
                $accommodationRequest = AccommodationRequest::with('status')
                    ->where('guest_id', $guestId)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $accommodationStatus = $accommodationRequest && $accommodationRequest->status 
                    ? strtolower($accommodationRequest->status->name) 
                    : null;
                
                return [
                    'id' => $invitation->id,
                    'guest_id' => $invitation->guest_id,
                    'guest_name' => $invitation->guest->name,
                    'guest_email' => $invitation->guest->email,
                    'guest_title' => $invitation->guest->title,
                    'guest_tier' => $invitation->guest->tier,
                    'guest_group' => $invitation->guest->group?->name,
                    'guest_status' => $invitation->guest->status?->name ?? 'pending',
                    'status' => $invitation->status,
                    'subject' => $invitation->subject,
                    'body' => $invitation->body,
                    'sent_at' => $invitation->sent_at?->format('Y-m-d H:i:s'),
                    'responded_at' => $invitation->responded_at?->format('Y-m-d H:i:s'),
                    'rsvp_token' => $invitation->rsvp_token,
                    'services' => [
                        'flight' => $flightStatus,
                        'accommodation' => $accommodationStatus,
                        'seat' => null, // TODO: Add seat assignment logic
                        'transport' => null, // TODO: Add transport logic
                        'ad' => null, // TODO: Add arrival/departure logic
                    ],
                    'matches' => $invitation->matches->map(function ($match) {
                        return [
                            'id' => $match->id,
                            'name' => $match->name,
                            'stage' => $match->stage_code ?? $match->stage,
                            'homeTeam' => $match->team_a_name,
                            'awayTeam' => $match->team_b_name,
                            'venue' => $match->venue?->name,
                            'date' => $match->date,
                            'time' => $match->time,
                            'response' => $match->pivot->response,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();

        return Inertia::render('Gms/Invitations/Index', [
            'invitations'    => $invitations,
            'tiers'          => GmsMockData::getTiers(),
            'emailTemplates' => GmsMockData::getEmailTemplates(),
            'event'          => $event,
        ]);
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

        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        if (!$eventId) {
            return back()->with('error', 'No active event selected.');
        }

        // Fetch guest
        $guest = Guest::findOrFail($validated['guestId']);

        // Parse match IDs (they come as 'M01', 'M14', etc. from frontend)
        // Strip 'M' prefix and convert to integers
        $matchIdIntegers = array_map(function($id) {
            return (int) str_replace('M', '', $id);
        }, $validated['matchIds']);

        // Fetch match details for merge tags and email
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

        // Replace merge tags in subject and body
        $tierName = $guest->tierInfo?->name ?? 'Guest';
        $matchList = collect($matches)->map(function($m) {
            return "• {$m['stageLabel']} — {$m['homeTeam']} vs {$m['awayTeam']} ({$m['date']}, {$m['kickoff']})";
        })->join("\n");

        $replacements = [
            '{{guest_name}}' => $guest->name,
            '{{tier_name}}'  => $tierName,
            '{{event_name}}' => $event['name'] ?? 'Event',
            '{{venue}}'      => $event['venue'] ?? '',
            '{{match_list}}' => $matchList ?: '(No matches selected)',
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $validated['subject']);
        $body = str_replace(array_keys($replacements), array_values($replacements), $validated['body']);

        // Create invitation
        $invitation = Invitation::create([
            'guest_id'  => $guest->id,
            'event_id'  => $eventId,
            'subject'   => $subject,
            'body'      => $body,
            'status'    => 'sent',
            'sent_at'   => now(),
        ]);

        // Attach matches to invitation
        foreach ($matchIdIntegers as $matchId) {
            $invitation->matches()->attach($matchId);
        }

        // Queue email
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

        // Update all match responses to 'yes'
        foreach ($invitation->matches as $match) {
            $invitation->matches()->updateExistingPivot($match->id, [
                'response' => 'yes',
            ]);
        }

        // Update invitation status based on responses and set responded_at timestamp
        $invitation->update(['responded_at' => now()]);
        $invitation->updateStatusFromResponses(); // Will set to 'accepted' since all are 'yes'

        // Update guest status to confirmed if it exists
        if ($invitation->guest) {
            $confirmedStatus = \App\Models\GuestStatus::where('name', 'confirmed')->first();
            if ($confirmedStatus) {
                $invitation->guest->update([
                    'status_id' => $confirmedStatus->id,
                ]);
            }
        }

        return back()->with('success', 'Invitation accepted on behalf of ' . $invitation->guest->name . '. All matches accepted.');
    }

    public function markConfirmed(Request $request, $id)
    {
        $invitation = Invitation::with(['guest'])->findOrFail($id);

        // Admin override: set invitation status to confirmed
        $invitation->update([
            'status' => 'confirmed',
            'responded_at' => $invitation->responded_at ?? now(), // Set responded_at if not already set
        ]);

        // Update guest status to confirmed if it exists
        if ($invitation->guest) {
            $confirmedStatus = \App\Models\GuestStatus::where('name', 'confirmed')->first();
            if ($confirmedStatus) {
                $invitation->guest->update([
                    'status_id' => $confirmedStatus->id,
                ]);
            }
        }

        return back()->with('success', $invitation->guest->name . ' has been marked as confirmed.');
    }

    public function markDeclined(Request $request, $id)
    {
        $invitation = Invitation::with(['guest'])->findOrFail($id);

        // Admin override: set invitation status to declined
        $invitation->update([
            'status' => 'declined',
            'responded_at' => $invitation->responded_at ?? now(), // Set responded_at if not already set
        ]);

        // Update guest status to declined if it exists
        if ($invitation->guest) {
            $declinedStatus = \App\Models\GuestStatus::where('name', 'declined')->first();
            if ($declinedStatus) {
                $invitation->guest->update([
                    'status_id' => $declinedStatus->id,
                ]);
            }
        }

        return back()->with('success', $invitation->guest->name . ' has been marked as declined.');
    }

    public function resetToPending(Request $request, $id)
    {
        $invitation = Invitation::with(['guest', 'matches'])->findOrFail($id);

        // Reset all match responses to null
        foreach ($invitation->matches as $match) {
            $invitation->matches()->updateExistingPivot($match->id, [
                'response' => null,
            ]);
        }

        // Reset invitation status to sent (pending response)
        $invitation->update([
            'status' => 'sent',
            'responded_at' => null, // Clear response timestamp
        ]);

        // Reset guest status to pending if it exists
        if ($invitation->guest) {
            $pendingStatus = \App\Models\GuestStatus::where('name', 'pending')->first();
            if ($pendingStatus) {
                $invitation->guest->update([
                    'status_id' => $pendingStatus->id,
                ]);
            }
        }

        return back()->with('success', $invitation->guest->name . ' has been reset to pending.');
    }
}
