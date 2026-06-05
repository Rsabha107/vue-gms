<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsMatchesController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/Matches/Index', [
            'matches' => GmsMockData::getMatches(),
            'venues' => GmsMockData::getVenues(),
            'event' => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'venueId' => 'required|string',
            'stageCode' => 'required|string',
            'stageLabel' => 'required|string',
            'homeTeam' => 'required|string',
            'homeCode' => 'nullable|string|max:3',
            'awayTeam' => 'required|string',
            'awayCode' => 'nullable|string|max:3',
            'date' => 'required|string',
            'kickoff' => 'required|string',
            'stage' => 'required|string',
        ]);

        // TODO: When DB is wired, create match record
        // Match::create($validated);

        return back()->with('success', 'Match created successfully');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'venueId' => 'required|string',
            'stageCode' => 'required|string',
            'stageLabel' => 'required|string',
            'homeTeam' => 'required|string',
            'homeCode' => 'nullable|string|max:3',
            'awayTeam' => 'required|string',
            'awayCode' => 'nullable|string|max:3',
            'date' => 'required|string',
            'kickoff' => 'required|string',
            'stage' => 'required|string',
        ]);

        // TODO: When DB is wired, update match record
        // $match = Match::findOrFail($id);
        // $match->update($validated);

        return back()->with('success', 'Match updated successfully');
    }

    public function destroy($id)
    {
        // TODO: When DB is wired, delete match record
        // Match::findOrFail($id)->delete();

        return back()->with('success', 'Match deleted successfully');
    }
}
