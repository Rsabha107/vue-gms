<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\ServiceLevel;
use App\Models\Group;
use App\Services\Gms\GmsMockData;
use App\Imports\GuestsImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class GmsGuestController extends Controller
{
    public function index()
    {
        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        return Inertia::render('Gms/Guests/Index', [
            'guests'  => Guest::with(['status', 'group'])
                ->when($eventId, fn($q) => $q->where('event_id', $eventId))
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray(),
            'tiers'   => ServiceLevel::orderBy('rank')->get()->toArray(),
            'groups'  => Group::orderBy('name')->get()->toArray(),
            'hosts'   => GmsMockData::getHosts(),
            'hotels'  => GmsMockData::getHotels(),
            'event'   => $event,
            'matches' => GmsMockData::getMatches(),
            'venues'  => GmsMockData::getVenues(),
            'emailTemplates' => GmsMockData::getEmailTemplates(),
        ]);
    }

    public function store(Request $request)
    {
        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'firstName'    => 'required|string|max:120',
            'lastName'     => 'required|string|max:120',
            'title'        => 'nullable|string|max:255',
            'guestType'    => 'required|in:local,international',
            'qid'          => 'nullable|string|max:20',
            'tier'         => 'required|string',
            'group_id'     => 'nullable|string',
            'nationality'  => 'nullable|string|max:2',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:40',
            'host'         => 'nullable|string',
            'hotel'        => 'nullable|string',
            'dietaryNotes' => 'nullable|string',
            'notes'        => 'nullable|string',
            'status_id'    => 'required|in:invited,confirmed,pending,declined',
            'flightPreferences' => 'nullable|string',
            'accommodationPreferences' => 'nullable|string',
            'transportationPreferences' => 'nullable|string',
            'companionList' => 'nullable|array',
            'companions'   => 'nullable|integer',
            'facilities'   => 'nullable|array',
            'facilityOverrides' => 'nullable|array',
        ]);

        // Generate reference number
        $lastGuest = Guest::when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('reference_number', 'desc')
            ->first();
        if ($lastGuest && $lastGuest->reference_number) {
            $lastNumber = (int) substr($lastGuest->reference_number, 1);
            $validated['reference_number'] = 'G' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $validated['reference_number'] = 'G001';
        }

        $validated['event_id'] = $eventId;

        Guest::create($validated);

        return back()->with('success', 'Guest created.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'firstName'    => 'nullable|string|max:120',
            'lastName'     => 'nullable|string|max:120',
            'title'        => 'nullable|string|max:255',
            'guestType'    => 'required|in:local,international',
            'qid'          => 'nullable|string|max:20',
            'tier'         => 'required|string',
            'group_id'     => 'nullable|string',
            'nationality'  => 'nullable|string|max:2',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:40',
            'host'         => 'nullable|string',
            'hotel'        => 'nullable|string',
            'dietaryNotes' => 'nullable|string',
            'notes'        => 'nullable|string',
            'status_id'    => 'required|in:invited,confirmed,pending,declined',
            'flightPreferences' => 'nullable|string',
            'accommodationPreferences' => 'nullable|string',
            'transportationPreferences' => 'nullable|string',
            'companionList' => 'nullable|array',
            'companions'   => 'nullable|integer',
            'facilities'   => 'nullable|array',
            'facilityOverrides' => 'nullable|array',
        ]);

        $guest = Guest::findOrFail($id);
        $guest->update($validated);

        return back()->with('success', 'Guest updated.');
    }

    public function destroy(string $id)
    {
        $guest = Guest::findOrFail($id);
        $guest->delete();

        return back()->with('success', 'Guest deleted.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            // Get current event
            $event = GmsMockData::getEvent();
            $eventId = $event['id'] ?? null;

            if (!$eventId) {
                return back()->with('error', 'No active event selected.');
            }

            // Create import instance
            $import = new GuestsImport($eventId);
            
            // Import the file
            Excel::import($import, $request->file('file'));

            $imported = $import->getImported();
            $skipped = $import->getSkipped();

            $message = "{$imported} guest(s) imported successfully.";
            if ($skipped > 0) {
                $message .= " {$skipped} row(s) skipped due to validation errors.";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
