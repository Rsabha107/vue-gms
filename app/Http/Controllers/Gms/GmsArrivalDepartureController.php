<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsArrivalDepartureController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/ArrivalDeparture/Index', [
            'requests' => GmsMockData::getArrivalDepartureRequests(),
            'guests'   => GmsMockData::getGuests(),
            'tiers'    => GmsMockData::getTiers(),
            'event'    => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'guestId'  => 'required|string',
            'type'     => 'required|in:arrival,departure',
            'flightNo' => 'required|string|max:20',
            'terminal' => 'required|string|max:60',
            'datetime' => 'required|string',
        ]);

        // TODO: persist
        return back()->with('success', 'A&D request created.');
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
        return back()->with('success', 'Request deleted.');
    }
}
