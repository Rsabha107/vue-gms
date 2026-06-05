<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsGuestController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/Guests/Index', [
            'guests'  => GmsMockData::getGuests(),
            'tiers'   => GmsMockData::getTiers(),
            'groups'  => GmsMockData::getGroups(),
            'hosts'   => GmsMockData::getHosts(),
            'hotels'  => GmsMockData::getHotels(),
            'event'   => GmsMockData::getEvent(),
            'matches' => GmsMockData::getMatches(),
            'venues'  => GmsMockData::getVenues(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'firstName'   => 'required|string|max:120',
            'lastName'    => 'required|string|max:120',
            'tier'        => 'required|string',
            'nationality' => 'nullable|string|max:2',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:40',
            'status'      => 'required|in:invited,confirmed,pending,declined',
        ]);

        // TODO: persist to DB
        return back()->with('success', 'Guest created.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'tier'   => 'required|string',
            'status' => 'required|in:invited,confirmed,pending,declined',
        ]);

        // TODO: persist to DB
        return back()->with('success', 'Guest updated.');
    }

    public function destroy(string $id)
    {
        // TODO: delete from DB
        return back()->with('success', 'Guest deleted.');
    }
}
