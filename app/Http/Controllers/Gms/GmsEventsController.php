<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsEventsController extends Controller
{
    public function index()
    {
        // Fetch all events (both active and inactive for management)
        $events = Event::with('venues')->orderBy('date_start', 'desc')->get()->map(function ($event) {
            return [
                'id' => $event->id,
                'name' => $event->name,
                'subtitle' => $event->subtitle,
                'location' => $event->location,
                'venue' => $event->venues->pluck('name')->join(', '),
                'venues' => $event->venues->map(fn($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                ])->toArray(),
                'date_start' => $event->date_start,
                'date_end' => $event->date_end,
                'logo' => $event->logo,
                'active_flag' => $event->active_flag,
                'dates' => $event->formatted_dates,
            ];
        });

        return Inertia::render('Gms/Events/Index', [
            'events' => $events,
            'event' => GmsMockData::getEvent(),
            'availableVenues' => GmsMockData::getVenues(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'venue_ids' => 'required|array',
            'venue_ids.*' => 'integer|exists:venues,id',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'logo' => 'nullable|string|max:10',
            'active_flag' => 'boolean',
        ]);

        // Remove venue_ids from validated data before creating event
        $venueIds = $validated['venue_ids'];
        unset($validated['venue_ids']);

        $event = Event::create($validated);
        $event->venues()->attach($venueIds);

        return back()->with('success', 'Event created successfully.');
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'venue_ids' => 'required|array',
            'venue_ids.*' => 'integer|exists:venues,id',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'logo' => 'nullable|string|max:10',
            'active_flag' => 'boolean',
        ]);

        // Remove venue_ids from validated data before updating event
        $venueIds = $validated['venue_ids'];
        unset($validated['venue_ids']);

        $event->update($validated);
        $event->venues()->sync($venueIds);

        return back()->with('success', 'Event updated successfully.');
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return back()->with('success', 'Event deleted successfully.');
    }
}
