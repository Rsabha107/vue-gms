<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\AccommodationRequest;
use App\Models\Guest;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class GmsAccommodationController extends Controller
{
    public function index()
    {
        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        // Fetch accommodation requests with relationships, filtered by event
        $accommodationRequests = AccommodationRequest::with(['guest', 'status'])
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ar) {
                return [
                    'id' => $ar->code,
                    'guestId' => $ar->guest_id,
                    'guestName' => $ar->guest->name ?? '',
                    'status' => $ar->status_id,
                    'hotel' => $ar->hotel_code,
                    'hotelName' => $ar->hotel_name,
                    'roomType' => $ar->room_type,
                    'checkIn' => $ar->check_in->format('Y-m-d'),
                    'checkOut' => $ar->check_out->format('Y-m-d'),
                    'nights' => $ar->nights,
                    'notes' => $ar->notes ?? '',
                ];
            });

        return Inertia::render('Gms/Accommodation/Index', [
            'requests' => $accommodationRequests,
            'guests'   => GmsMockData::getGuests(),
            'hotels'   => GmsMockData::getHotels(),
            'tiers'    => GmsMockData::getTiers(),
            'event'    => $event,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guestId'  => 'required|exists:guests,id',
            'hotel'    => 'required|string',
            'roomType' => 'required|string|max:80',
            'checkIn'  => 'required|date',
            'checkOut' => 'required|date|after:checkIn',
            'notes'    => 'nullable|string',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? 1;

        // Generate unique code
        $lastCode = AccommodationRequest::where('event_id', $eventId)
            ->orderBy('id', 'desc')
            ->value('code');
        $nextNumber = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 1;
        $code = 'ACC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Calculate nights
        $checkIn = Carbon::parse($validated['checkIn']);
        $checkOut = Carbon::parse($validated['checkOut']);
        $nights = $checkIn->diffInDays($checkOut);

        AccommodationRequest::create([
            'event_id' => $eventId,
            'guest_id' => $validated['guestId'],
            'code' => $code,
            'status_id' => 'new',
            'hotel_code' => $validated['hotel'],
            'hotel_name' => GmsMockData::getHotels()[array_search($validated['hotel'], array_column(GmsMockData::getHotels(), 'id'))][ 'name'] ?? $validated['hotel'],
            'room_type' => $validated['roomType'],
            'check_in' => $validated['checkIn'],
            'check_out' => $validated['checkOut'],
            'nights' => $nights,
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Accommodation request created.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'guestId'  => 'required|exists:guests,id',
            'hotel'    => 'required|string',
            'roomType' => 'required|string|max:80',
            'checkIn'  => 'required|date',
            'checkOut' => 'required|date|after:checkIn',
            'notes'    => 'nullable|string',
        ]);

        $accommodationRequest = AccommodationRequest::where('code', $id)->firstOrFail();

        // Calculate nights
        $checkIn = Carbon::parse($validated['checkIn']);
        $checkOut = Carbon::parse($validated['checkOut']);
        $nights = $checkIn->diffInDays($checkOut);

        $accommodationRequest->update([
            'guest_id' => $validated['guestId'],
            'hotel_code' => $validated['hotel'],
            'hotel_name' => GmsMockData::getHotels()[array_search($validated['hotel'], array_column(GmsMockData::getHotels(), 'id'))]['name'] ?? $validated['hotel'],
            'room_type' => $validated['roomType'],
            'check_in' => $validated['checkIn'],
            'check_out' => $validated['checkOut'],
            'nights' => $nights,
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Accommodation request updated.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate(['status' => 'required|in:new,change,confirmed,cancelled']);

        $accommodationRequest = AccommodationRequest::where('code', $id)->firstOrFail();
        $accommodationRequest->update(['status_id' => $validated['status']]);

        return back()->with('success', 'Status updated.');
    }

    public function destroy(string $id)
    {
        $accommodationRequest = AccommodationRequest::where('code', $id)->firstOrFail();
        $accommodationRequest->delete();

        return back()->with('success', 'Accommodation request deleted.');
    }
}
