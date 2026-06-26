<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationStatus;
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
    /**
     * Cache invitation status IDs
     */
    private static $statusIds = null;

    private function getStatusId(string $statusName): ?int
    {
        if (self::$statusIds === null) {
            self::$statusIds = InvitationStatus::pluck('id', 'name')->toArray();
        }
        return self::$statusIds[$statusName] ?? null;
    }

    /** GET /rsvp/{token} */
    public function show(string $token): Response
    {
        $invitation = Invitation::with(['guest', 'event', 'matches.venue', 'status'])
            ->where('rsvp_token', $token)
            ->firstOrFail();

        // Load existing companions from guest_event pivot
        $guest = $invitation->guest;
        $pivot = $guest->events()->where('event_id', $invitation->event_id)->first()?->pivot;
        $existingCompanions = $pivot?->companions ?? [];

        return Inertia::render('Rsvp/Show', [
            'token' => $token,
            'event' => [
                'name'  => $invitation->event->name,
                'venue' => $invitation->event->venue ?? '',
                'dates' => $invitation->event->formatted_dates ?? '',
            ],
            'guest'    => ['name' => $guest->name],
            'subject'  => $invitation->subject,
            'body'     => $invitation->body,
            'companions' => $existingCompanions,
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
            'responses'      => ['required', 'array', 'min:1'],
            'responses.*'    => ['nullable', 'in:yes,no'],
            'passport_no'    => ['nullable', 'string', 'max:40'],
            'personal_photo' => ['nullable', 'string', 'max:255'],
            'passport_front' => ['nullable', 'string', 'max:255'],
            'photo_consent'  => ['nullable', 'boolean'],
            'companions'              => ['nullable', 'array'],
            'companions.*.name'           => ['required_with:companions', 'string', 'max:120'],
            'companions.*.relation'       => ['nullable', 'string', 'max:60'],
            'companions.*.passport_no'    => ['nullable', 'string', 'max:40'],
            'companions.*.personal_photo' => ['nullable', 'string', 'max:255'],
            'companions.*.passport_front' => ['nullable', 'string', 'max:255'],
        ]);

        // Update match responses
        foreach ($data['responses'] as $matchId => $response) {
            $invitation->matches()->updateExistingPivot((int) $matchId, [
                'response' => $response,
            ]);
        }

        // Save guest documents and companions to guest_event pivot
        if ($invitation->guest && $invitation->event_id) {
            $pivotUpdates = array_filter([
                'passport_no'    => $data['passport_no'] ?? null,
                'personal_photo' => $data['personal_photo'] ?? null,
                'passport_front' => $data['passport_front'] ?? null,
            ], fn($v) => $v !== null);

            if (!empty($data['companions'])) {
                $pivotUpdates['companions'] = collect($data['companions'])
                    ->filter(fn($c) => !empty($c['name']))
                    ->map(fn($c) => [
                        'name'           => $c['name'],
                        'relation'       => $c['relation'] ?? 'Companion',
                        'passport_no'    => $c['passport_no'] ?? '',
                        'personal_photo' => $c['personal_photo'] ?? '',
                        'passport_front' => $c['passport_front'] ?? '',
                    ])->values()->toArray();
            }

            if (!empty($pivotUpdates)) {
                $invitation->guest->events()->updateExistingPivot($invitation->event_id, $pivotUpdates);
            }
        }

        // Update invitation status based on responses and set responded_at timestamp
        $invitation->update(['responded_at' => now()]);
        $invitation->updateStatusFromResponses();
        
        // Reload invitation to get updated status relationship
        $invitation->load('status');

        // Update guest status based on invitation status
        $invitationStatusName = $invitation->status?->name ?? 'pending';
        $guestStatusName = match($invitationStatusName) {
            'accepted' => 'accepted',   // Keep statuses aligned
            'pending' => 'pending',
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
                'status_id' => $this->getStatusId($pivotStatus),
            ]);
        }

        return back()->with('success', 'Your RSVP has been recorded. Thank you!');
    }
}
