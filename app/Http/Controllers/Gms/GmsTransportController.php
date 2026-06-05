<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsTransportController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/Transport/Index', [
            'requests' => GmsMockData::getTransportRequests(),
            'guests'   => GmsMockData::getGuests(),
            'tiers'    => GmsMockData::getTiers(),
            'event'    => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'guestId'        => 'required|string',
            'type'           => 'required|string|max:60',
            'vehicle'        => 'required|string|max:80',
            'pickupLocation' => 'required|string|max:120',
            'dropoffLocation'=> 'required|string|max:120',
            'datetime'       => 'required|string',
        ]);

        // TODO: persist
        return back()->with('success', 'Transport request created.');
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
        return back()->with('success', 'Transport request deleted.');
    }
}
