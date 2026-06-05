<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsServiceLevelController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/ServiceLevels/Index', [
            'tiers'  => GmsMockData::getTiers(),
            'guests' => GmsMockData::getGuests(),
            'event'  => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:60',
            'color'      => 'required|string|max:9',
            'facilities' => 'nullable|array',
        ]);

        // TODO: persist
        return back()->with('success', 'Service level created.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name'  => 'required|string|max:60',
            'color' => 'required|string|max:9',
        ]);

        // TODO: persist
        return back()->with('success', 'Service level updated.');
    }

    public function destroy(string $id)
    {
        // TODO: delete
        return back()->with('success', 'Service level deleted.');
    }
}
