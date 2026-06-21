<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsGroupsController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();

        return Inertia::render('Gms/Groups/Index', [
            'groups' => Group::withCount('guests')
                ->orderBy('name')
                ->get()
                ->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'label' => $group->label,
                        'guestCount' => $group->guests_count,
                        'created_at' => $group->created_at?->format('Y-m-d H:i:s'),
                        'updated_at' => $group->updated_at?->format('Y-m-d H:i:s'),
                    ];
                })
                ->toArray(),
            'event' => $event,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|max:20|unique:groups,id',
            'name' => 'required|string|max:50',
            'label' => 'required|string|max:150',
        ]);

        Group::create($validated);

        return back()->with('success', 'Group created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'label' => 'required|string|max:150',
        ]);

        $group = Group::findOrFail($id);
        $group->update($validated);

        return back()->with('success', 'Group updated successfully.');
    }

    public function destroy(string $id)
    {
        $group = Group::findOrFail($id);
        
        // Check if group has guests
        if ($group->guests()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete group with assigned guests.']);
        }

        $group->delete();

        return back()->with('success', 'Group deleted successfully.');
    }
}
