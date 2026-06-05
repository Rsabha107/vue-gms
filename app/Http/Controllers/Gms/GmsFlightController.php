<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsFlightController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/Flights/Index', [
            'requests' => GmsMockData::getFlightRequests(),
            'guests'   => GmsMockData::getGuests(),
            'tiers'    => GmsMockData::getTiers(),
            'event'    => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'guestId'  => 'required|string',
            'flightNo' => 'required|string|max:20',
            'route'    => 'required|string|max:20',
            'class'    => 'required|string',
            'pax'      => 'required|integer|min:1',
            'date'     => 'required|date',
        ]);

        // TODO: persist
        return back()->with('success', 'Flight request created.');
    }

    public function update(Request $request, string $id)
    {
        // TODO: persist
        return back()->with('success', 'Flight updated.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $request->validate(['status' => 'required|in:pending,confirmed,cancelled']);
        // TODO: persist
        return back()->with('success', 'Status updated.');
    }

    public function destroy(string $id)
    {
        // TODO: delete
        return back()->with('success', 'Flight request deleted.');
    }
}
