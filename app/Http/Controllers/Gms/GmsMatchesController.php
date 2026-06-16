<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GameMatch;
use App\Models\Venue;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class GmsMatchesController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        // Fetch matches with venue relationship, filtered by event
        $matches = GameMatch::with('venue')
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(function ($match) {
                // Format date as "Mon 10 Aug 2026"
                $formattedDate = $match->date ? 
                    ($match->day ? $match->day . ' ' : '') . 
                    $match->date->format('j M Y') : '';
                
                // Calculate seats - use capacity when no seats created yet
                $seatStats = $match->seatStats();
                $seatsTotal = $seatStats['total'] > 0 ? $seatStats['total'] : $match->capacity;
                $assigned = $seatStats['assigned'] ?? 0;
                $seatsLeft = $seatsTotal - $assigned;
                
                return [
                    'id' => $match->id,
                    'venueId' => $match->venue_id,
                    'venueName' => $match->venue->name ?? '',
                    'featured' => $match->featured,
                    'name' => $match->label,
                    'stageCode' => strtoupper(str_replace(' ', '_', $match->stage ?? '')),
                    'stageLabel' => $match->label,
                    'homeTeam' => $match->team_a_name,
                    'homeCode' => $match->team_a_flag,
                    'awayTeam' => $match->team_b_name,
                    'awayCode' => $match->team_b_flag,
                    'date' => $formattedDate,
                    'rawDate' => $match->date ? $match->date->format('Y-m-d') : '', // For date input
                    'kickoff' => $match->time,
                    'stage' => $match->stage,
                    'seatsLeft' => $seatsLeft,
                    'seatsTotal' => $seatsTotal,
                    'capacity' => $match->capacity,
                    'sold' => $match->sold,
                    'bracketTBD' => $match->tbd,
                ];
            });

        // Get venues assigned to this event
        $venues = [];
        if ($eventId) {
            $eventModel = Event::find($eventId);
            if ($eventModel) {
                $venues = $eventModel->venues()->orderBy('name')->get()->map(fn($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'city' => $v->city,
                    'country' => $v->country,
                    'capacity' => $v->capacity,
                ]);
            }
        }

        return Inertia::render('Gms/Matches/Index', [
            'matches' => $matches,
            'venues' => $venues,
            'event' => $event,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'venueId' => 'required|integer',
            'stageCode' => 'required|string',
            'stageLabel' => 'required|string',
            'homeTeam' => 'required|string',
            'homeCode' => 'nullable|string|max:3',
            'awayTeam' => 'required|string',
            'awayCode' => 'nullable|string|max:3',
            'date' => 'required|string',
            'kickoff' => 'required|string',
            'stage' => 'required|string',
            'capacity' => 'nullable|integer|min:0',
            'sold' => 'nullable|integer|min:0',
            'featured' => 'nullable|boolean',
            'tbd' => 'nullable|boolean',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? 1;

        // Parse date - could be "10 Aug 2026", "Mon 10 Aug 2026", or "2026-08-10" (YYYY-MM-DD)
        // Check if it's in YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $validated['date'])) {
            try {
                $parsedDate = Carbon::createFromFormat('Y-m-d', $validated['date']);
            } catch (\Exception $e) {
                return back()->withErrors(['date' => 'Invalid date format.']);
            }
        } else {
            // Parse human-readable format - could be "10 Aug 2026" or "Mon 10 Aug 2026"
            $dateParts = explode(' ', trim($validated['date']));
            $hasDay = count($dateParts) === 4; // has day prefix
            $dateStr = $hasDay ? implode(' ', array_slice($dateParts, 1)) : $validated['date'];
            
            try {
                $parsedDate = Carbon::createFromFormat('j M Y', $dateStr);
            } catch (\Exception $e) {
                return back()->withErrors(['date' => 'Invalid date format. Use format like "10 Aug 2026" or "2026-08-10".']);
            }
        }

        // Always extract day from parsed date
        $day = $parsedDate->format('D');

        GameMatch::create([
            'event_id' => $eventId,
            'venue_id' => $validated['venueId'],
            'stage' => $validated['stage'],
            'label' => $validated['stageLabel'],
            'team_a_name' => $validated['homeTeam'],
            'team_a_flag' => $validated['homeCode'],
            'team_b_name' => $validated['awayTeam'],
            'team_b_flag' => $validated['awayCode'],
            'date' => $parsedDate->format('Y-m-d'),
            'day' => $day,
            'time' => $validated['kickoff'],
            'capacity' => $validated['capacity'] ?? 320,
            'sold' => $validated['sold'] ?? 0,
            'featured' => $validated['featured'] ?? false,
            'tbd' => $validated['tbd'] ?? false,
        ]);

        return back()->with('success', 'Match created successfully');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'venueId' => 'required|integer',
            'stageCode' => 'required|string',
            'stageLabel' => 'required|string',
            'homeTeam' => 'required|string',
            'homeCode' => 'nullable|string|max:3',
            'awayTeam' => 'required|string',
            'awayCode' => 'nullable|string|max:3',
            'date' => 'required|string',
            'kickoff' => 'required|string',
            'stage' => 'required|string',
            'capacity' => 'nullable|integer|min:0',
            'sold' => 'nullable|integer|min:0',
            'featured' => 'nullable|boolean',
            'tbd' => 'nullable|boolean',
        ]);

        $match = GameMatch::findOrFail($id);

        // Parse date - could be "10 Aug 2026", "Mon 10 Aug 2026", or "2026-08-10" (YYYY-MM-DD)
        // Check if it's in YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $validated['date'])) {
            try {
                $parsedDate = Carbon::createFromFormat('Y-m-d', $validated['date']);
            } catch (\Exception $e) {
                return back()->withErrors(['date' => 'Invalid date format.']);
            }
        } else {
            // Parse human-readable format - could be "10 Aug 2026" or "Mon 10 Aug 2026"
            $dateParts = explode(' ', trim($validated['date']));
            $hasDay = count($dateParts) === 4; // has day prefix
            $dateStr = $hasDay ? implode(' ', array_slice($dateParts, 1)) : $validated['date'];
            
            try {
                $parsedDate = Carbon::createFromFormat('j M Y', $dateStr);
            } catch (\Exception $e) {
                return back()->withErrors(['date' => 'Invalid date format. Use format like "10 Aug 2026" or "2026-08-10".']);
            }
        }

        // Always extract day from parsed date
        $day = $parsedDate->format('D');

        $match->update([
            'venue_id' => $validated['venueId'],
            'stage' => $validated['stage'],
            'label' => $validated['stageLabel'],
            'team_a_name' => $validated['homeTeam'],
            'team_a_flag' => $validated['homeCode'],
            'team_b_name' => $validated['awayTeam'],
            'team_b_flag' => $validated['awayCode'],
            'date' => $parsedDate->format('Y-m-d'),
            'day' => $day,
            'time' => $validated['kickoff'],
            'capacity' => $validated['capacity'] ?? 320,
            'sold' => $validated['sold'] ?? 0,
            'featured' => $validated['featured'] ?? false,
            'tbd' => $validated['tbd'] ?? false,
        ]);

        return back()->with('success', 'Match updated successfully');
    }

    public function destroy($id)
    {
        $match = GameMatch::findOrFail($id);
        $match->delete();

        return back()->with('success', 'Match deleted successfully');
    }
}
