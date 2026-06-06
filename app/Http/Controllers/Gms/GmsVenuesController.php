<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsVenuesController extends Controller
{
    public function index()
    {
        $matches = GmsMockData::getMatches();

        $venues = Venue::withCount('events')->whereNotNull('name')->where('name', '!=', '')->orderByDesc('capacity')->get()->map(function ($venue) use ($matches) {
            $matchCount = count(array_filter($matches, fn($m) => $m['venueName'] === $venue->name));
            return [
                'id'           => $venue->id,
                'name'         => $venue->name,
                'city'         => $venue->city,
                'country'      => $venue->country,
                'capacity'     => $venue->capacity,
                'type'         => $venue->type,
                'notes'        => $venue->notes,
                'events_count' => $venue->events_count,
                'matches_count'=> $matchCount,
            ];
        });

        return Inertia::render('Gms/Venues/Index', [
            'venues' => $venues,
            'event'  => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'city'     => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'notes'    => 'nullable|string',
        ]);

        Venue::create($validated);

        return back()->with('success', 'Venue created.');
    }

    public function update(Request $request, int $id)
    {
        $venue = Venue::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'city'     => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'notes'    => 'nullable|string',
        ]);

        $venue->update($validated);

        return back()->with('success', 'Venue updated.');
    }

    public function destroy(int $id)
    {
        Venue::findOrFail($id)->delete();

        return back()->with('success', 'Venue deleted.');
    }
}
