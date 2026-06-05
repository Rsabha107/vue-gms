<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsAccommodationController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/Accommodation/Index', [
            'requests' => GmsMockData::getAccommodationRequests(),
            'guests'   => GmsMockData::getGuests(),
            'hotels'   => GmsMockData::getHotels(),
            'tiers'    => GmsMockData::getTiers(),
            'event'    => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'guestId'  => 'required|string',
            'hotel'    => 'required|string',
            'roomType' => 'required|string|max:80',
            'checkIn'  => 'required|date',
            'checkOut' => 'required|date|after:checkIn',
        ]);

        // TODO: persist
        return back()->with('success', 'Accommodation request created.');
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
        return back()->with('success', 'Accommodation request deleted.');
    }
}
