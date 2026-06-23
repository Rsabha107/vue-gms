<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\ServiceLevel;
use App\Models\Group;
use App\Models\Nationality;
use App\Services\Gms\GmsMockData;
use App\Imports\GuestsImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class GmsGuestController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        $allEvents = GmsMockData::getEvents();

        return Inertia::render('Gms/Guests/Index', [
            'guests'  => Guest::with([
                'status',
                'group',
                'events',
                'invitations.matches',
                'invitations.status',
                'flightRequests.legs',
                'accommodationRequests',
                'transportRequests',
                'arrivalDepartureRequests',
                'seats.match',
                'seats.block'
            ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($guest) use ($allEvents) {
                    $guestArray = $guest->toArray();

                    // Build attendance map: { eventId: { status, added_at, invited_at } }
                    $attendance = [];
                    foreach ($guest->events as $ev) {
                        $attendance[$ev->id] = [
                            'status'     => $ev->pivot->status,
                            'added_at'   => $ev->pivot->added_at,
                            'invited_at' => $ev->pivot->invited_at,
                        ];
                    }
                    $guestArray['attendance'] = (object) $attendance;

                    // Map service statuses
                    $serviceStatuses = [
                        'flights' => null,
                        'accommodation' => null,
                        'seating' => null,
                        'transport' => null,
                        'arrival' => null,
                    ];

                    if ($guest->flightRequests->isNotEmpty()) {
                        $latestFlight = $guest->flightRequests->sortByDesc('created_at')->first();
                        $serviceStatuses['flights'] = $latestFlight->status ?? 'pending';
                    }

                    if ($guest->accommodationRequests->isNotEmpty()) {
                        $latestAccomm = $guest->accommodationRequests->sortByDesc('created_at')->first();
                        $serviceStatuses['accommodation'] = $latestAccomm->status_id ?? 'pending';
                    }

                    if ($guest->seats->isNotEmpty()) {
                        $serviceStatuses['seating'] = 'confirmed';
                    }

                    if ($guest->transportRequests->isNotEmpty()) {
                        $latestTransport = $guest->transportRequests->sortByDesc('created_at')->first();
                        $serviceStatuses['transport'] = $latestTransport->status_id ?? 'pending';
                    }

                    if ($guest->arrivalDepartureRequests->isNotEmpty()) {
                        $latestAD = $guest->arrivalDepartureRequests->sortByDesc('created_at')->first();
                        $serviceStatuses['arrival'] = $latestAD->status ?? 'pending';
                    }

                    $guestArray['serviceStatuses'] = $serviceStatuses;

                    // Map invitations with match details
                    $guestArray['invitations'] = $guest->invitations->map(function ($invitation) {
                        return [
                            'id' => $invitation->id,
                            'status' => $invitation->status?->name ?? 'not_invited',
                            'subject' => $invitation->subject,
                            'body' => $invitation->body,
                            'sent_at' => $invitation->sent_at?->format('Y-m-d H:i:s'),
                            'responded_at' => $invitation->responded_at?->format('Y-m-d H:i:s'),
                            'rsvp_token' => $invitation->rsvp_token,
                            'matches' => $invitation->matches->map(function ($match) {
                                return [
                                    'id' => $match->id,
                                    'name' => $match->name,
                                    'stage' => $match->stage_code ?? $match->stage,
                                    'homeTeam' => $match->team_a_name,
                                    'awayTeam' => $match->team_b_name,
                                    'date' => $match->date,
                                    'time' => $match->time,
                                    'venue' => $match->venue?->name ?? 'TBD',
                                    'response' => $match->pivot->response,
                                ];
                            })->toArray(),
                        ];
                    })->toArray();

                    // Map seat assignments with match info
                    $guestArray['seatAssignments'] = $guest->seats->map(function ($seat) {
                        return [
                            'id' => $seat->id,
                            'seat_code' => $seat->code,
                            'block' => $seat->block?->name ?? null,
                            'block_label' => $seat->block?->label ?? null,
                            'match_id' => $seat->game_match_id,
                            'match_name' => $seat->match?->name ?? 'Unknown Match',
                            'status' => $seat->status,
                        ];
                    })->toArray();

                    // Map flight requests with leg info
                    $guestArray['flightInfo'] = $guest->flightRequests
                        ->filter(fn($f) => $f->status !== 'cancelled')
                        ->map(function ($flight) {
                            $inbound = $flight->legs->where('dir', 'Inbound')->first();
                            $outbound = $flight->legs->where('dir', 'Outbound')->first();
                            return [
                                'id' => $flight->id,
                                'code' => $flight->code,
                                'status' => $flight->status,
                                'route' => $inbound && $outbound 
                                    ? "{$inbound->from_code} → {$inbound->to_code}"
                                    : '—',
                                'inbound' => $inbound ? [
                                    'flight_no' => $inbound->flight_no,
                                    'date' => $inbound->date,
                                    'from' => $inbound->from_code,
                                    'to' => $inbound->to_code,
                                ] : null,
                                'outbound' => $outbound ? [
                                    'flight_no' => $outbound->flight_no,
                                    'date' => $outbound->date,
                                    'from' => $outbound->from_code,
                                    'to' => $outbound->to_code,
                                ] : null,
                            ];
                        })->toArray();

                    return $guestArray;
                })
                ->toArray(),
            'tiers'   => ServiceLevel::orderBy('rank')->get()->toArray(),
            'groups'  => Group::orderBy('name')->get()->toArray(),
            'hosts'   => GmsMockData::getHosts(),
            'hotels'  => GmsMockData::getHotels(),
            'event'   => $event,
            'matches' => GmsMockData::getMatches(),
            'venues'  => GmsMockData::getVenues(),
            'emailTemplates' => GmsMockData::getEmailTemplates(),
            'nationalities' => Nationality::getForDropdown(),
        ]);
    }

    public function store(Request $request)
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'firstName'    => 'required|string|max:120',
            'lastName'     => 'required|string|max:120',
            'title'        => 'nullable|string|max:255',
            'guestType'    => 'required|in:local,international',
            'qid'          => 'nullable|string|max:20',
            'tier'         => 'required|string',
            'group_id'     => 'nullable|string',
            'nationality'  => 'nullable|string|max:2',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:40',
            'host'         => 'nullable|string',
            'hotel'        => 'nullable|string',
            'dietaryNotes' => 'nullable|string',
            'notes'        => 'nullable|string',
            'status_id'    => 'required|in:invited,confirmed,pending,declined',
            'flightPreferences' => 'nullable|string',
            'accommodationPreferences' => 'nullable|string',
            'transportationPreferences' => 'nullable|string',
            'companionList' => 'nullable|array',
            'companions'   => 'nullable|integer',
            'facilities'   => 'nullable|array',
            'facilityOverrides' => 'nullable|array',
        ]);

        // Generate reference number
        $lastGuest = Guest::orderBy('reference_number', 'desc')->first();
        if ($lastGuest && $lastGuest->reference_number) {
            $lastNumber = (int) substr($lastGuest->reference_number, 1);
            $validated['reference_number'] = 'G' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $validated['reference_number'] = 'G001';
        }

        $validated['event_id'] = $eventId;

        $guest = Guest::create($validated);

        // Auto-add to current event if one is active
        if ($eventId) {
            $guest->events()->attach($eventId, [
                'status' => 'not_invited',
                'added_at' => now(),
            ]);
        }

        return back()->with('success', 'Guest created.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'firstName'    => 'nullable|string|max:120',
            'lastName'     => 'nullable|string|max:120',
            'title'        => 'nullable|string|max:255',
            'guestType'    => 'required|in:local,international',
            'qid'          => 'nullable|string|max:20',
            'tier'         => 'required|string',
            'group_id'     => 'nullable|string',
            'nationality'  => 'nullable|string|max:2',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:40',
            'host'         => 'nullable|string',
            'hotel'        => 'nullable|string',
            'dietaryNotes' => 'nullable|string',
            'notes'        => 'nullable|string',
            'status_id'    => 'required|in:invited,confirmed,pending,declined',
            'flightPreferences' => 'nullable|string',
            'accommodationPreferences' => 'nullable|string',
            'transportationPreferences' => 'nullable|string',
            'companionList' => 'nullable|array',
            'companions'   => 'nullable|integer',
            'facilities'   => 'nullable|array',
            'facilityOverrides' => 'nullable|array',
        ]);

        $guest = Guest::findOrFail($id);
        $guest->update($validated);

        return back()->with('success', 'Guest updated.');
    }

    public function destroy(string $id)
    {
        $guest = Guest::findOrFail($id);
        $guest->delete();

        return back()->with('success', 'Guest deleted.');
    }

    /**
     * Add one or more guests to the current event
     */
    public function addToEvent(Request $request)
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

        $eventName = $event['name'] ?? 'event';
        return back()->with('success', "{$added} guest(s) added to {$eventName}.");
    }

    /**
     * Remove a guest from the current event
     */
    public function removeFromEvent(Request $request)
    {
        $validated = $request->validate([
            'guest_id' => 'required|exists:guests,id',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        if (!$eventId) {
            return back()->with('error', 'No active event selected.');
        }

        $guest = Guest::findOrFail($validated['guest_id']);
        $guest->events()->detach($eventId);

        return back()->with('success', "{$guest->name} removed from {$event['name']}.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $event = GmsMockData::getEvent();
            $eventId = $event['id'] ?? null;

            if (!$eventId) {
                return back()->with('error', 'No active event selected.');
            }

            $import = new GuestsImport($eventId);

            Excel::import($import, $request->file('file'));

            $imported = $import->getImported();
            $skipped = $import->getSkipped();

            $message = "{$imported} guest(s) imported successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) skipped due to validation errors.";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
