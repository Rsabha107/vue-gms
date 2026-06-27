<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\AccommodationRequest;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\RoomBlock;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class GmsAccommodationController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        $allRequests = AccommodationRequest::with(['guest', 'status', 'hotel', 'fulfillsRequest'])
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('created_at', 'desc')
            ->get();

        $transform = function ($ar) {
            return [
                'id'                  => $ar->code,
                'guestId'             => $ar->guest_id,
                'guestName'           => $ar->guest->name ?? '',
                'status'              => $ar->status->name ?? 'new',
                'hotel'               => $ar->hotel_id,
                'hotelName'           => $ar->hotel->name ?? $ar->hotel_name,
                'roomType'            => $ar->room_type,
                'checkIn'             => $ar->check_in->format('Y-m-d'),
                'checkOut'            => $ar->check_out->format('Y-m-d'),
                'nights'              => $ar->nights,
                'notes'               => $ar->notes ?? '',
                'guestRemarks'        => $ar->guest_remarks,
                'source'              => $ar->source,
                'initiatedBy'         => $ar->initiated_by,
                'fulfilledById'       => $ar->fulfilled_by_id,
                'fulfillsRequestCode' => $ar->fulfillsRequest?->code,
                'submitted'           => $ar->created_at?->format('Y-m-d H:i'),
            ];
        };

        $guestRequests = $allRequests
            ->filter(fn($ar) => $ar->source === 'portal' && $ar->initiated_by === 'guest')
            ->map($transform)->values();

        $accommodationRequests = $allRequests
            ->filter(fn($ar) => !($ar->source === 'portal' && $ar->initiated_by === 'guest'))
            ->map($transform)->values();

        $roomBlocks = RoomBlock::with('hotel')
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('hotel_name')
            ->orderBy('room_type')
            ->get()
            ->map(fn(RoomBlock $rb) => [
                'id'         => $rb->id,
                'hotelId'    => $rb->hotel_id,
                'hotelName'  => $rb->hotel->name ?? $rb->hotel_name,
                'roomType'   => $rb->room_type,
                'rate'       => (float) $rb->rate,
                'currency'   => $rb->currency,
                'checkIn'    => $rb->check_in->format('Y-m-d'),
                'checkOut'   => $rb->check_out->format('Y-m-d'),
                'allotment'  => $rb->allotment,
                'pickedUp'   => $rb->picked_up,
                'cutoffDate' => $rb->cutoff_date?->format('Y-m-d'),
                'notes'      => $rb->notes ?? '',
            ]);

        return Inertia::render('Gms/Accommodation/Index', [
            'requests'      => $accommodationRequests,
            'guestRequests' => $guestRequests,
            'roomBlocks'    => $roomBlocks,
            'guests'     => Guest::where('guestType', 'international')
                ->when($eventId, fn($q) => $q->whereHas('events', fn($eq) => $eq->where('event_id', $eventId)))
                ->with(['tierInfo', 'group', 'events' => fn($q) => $q->where('event_id', $eventId)])
                ->orderBy('name')
                ->get()
                ->map(function($g) use ($eventId) {
                    $pivotStatus = $g->events->firstWhere('id', $eventId)?->pivot?->status_id;
                    $statusName = $pivotStatus ? (\App\Models\InvitationStatus::find($pivotStatus)?->name ?? null) : null;
                    return [
                        'id' => $g->id,
                        'name' => $g->name,
                        'tier' => $g->tier,
                        'guestType' => $g->guestType,
                        'group' => $g->group->name ?? null,
                        'email' => $g->email,
                        'invitationStatus' => $statusName,
                        'hasConfirmedInvitation' => $statusName === 'confirmed',
                    ];
                }),
            'hotels' => GmsMockData::getHotels(),
            'tiers'  => GmsMockData::getTiers(),
            'event'  => $event,
        ]);
    }

    // ── Accommodation request CRUD ──

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guestId'  => 'required|exists:guests,id',
            'hotel'    => 'required|exists:hotels,id',
            'roomType' => 'required|string|max:80',
            'checkIn'  => 'required|date',
            'checkOut' => 'required|date|after:checkIn',
            'notes'    => 'nullable|string',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? 1;

        $lastCode = AccommodationRequest::where('event_id', $eventId)
            ->orderBy('id', 'desc')
            ->value('code');
        $nextNumber = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 1;
        $code = 'ACC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $checkIn = Carbon::parse($validated['checkIn']);
        $checkOut = Carbon::parse($validated['checkOut']);
        $nights = $checkIn->diffInDays($checkOut);

        $hotel = Hotel::findOrFail($validated['hotel']);

        AccommodationRequest::create([
            'event_id'   => $eventId,
            'guest_id'   => $validated['guestId'],
            'code'       => $code,
            'status_id'  => \App\Models\InvitationStatus::where('name', 'pending')->value('id'),
            'hotel_id'   => $hotel->id,
            'hotel_code' => $hotel->code,
            'hotel_name' => $hotel->name,
            'room_type'  => $validated['roomType'],
            'check_in'   => $validated['checkIn'],
            'check_out'  => $validated['checkOut'],
            'nights'     => $nights,
            'notes'      => $validated['notes'],
        ]);

        $guest = Guest::find($validated['guestId']);
        if ($guest?->email) {
            $portalUrl = null;
            try { $portalUrl = \App\Services\Gms\PortalTokenService::generateSignedUrl($guest); } catch (\Throwable $e) {}
            \App\Services\Gms\ServiceConfirmationService::sendServiceReview($guest, $event['name'] ?? '', 'Accommodation', [
                'Booking Code' => $code,
                'Hotel'        => $hotel->name,
                'Room Type'    => $validated['roomType'],
                'Check-in'     => $validated['checkIn'],
                'Check-out'    => $validated['checkOut'],
                'Nights'       => $nights,
            ], $portalUrl);
        }

        return back()->with('success', 'Accommodation request created.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'guestId'  => 'required|exists:guests,id',
            'hotel'    => 'required|exists:hotels,id',
            'roomType' => 'required|string|max:80',
            'checkIn'  => 'required|date',
            'checkOut' => 'required|date|after:checkIn',
            'notes'    => 'nullable|string',
        ]);

        $accommodationRequest = AccommodationRequest::where('code', $id)->firstOrFail();

        $checkIn = Carbon::parse($validated['checkIn']);
        $checkOut = Carbon::parse($validated['checkOut']);
        $nights = $checkIn->diffInDays($checkOut);

        $hotel = Hotel::findOrFail($validated['hotel']);

        $accommodationRequest->update([
            'guest_id'   => $validated['guestId'],
            'hotel_id'   => $hotel->id,
            'hotel_code' => $hotel->code,
            'hotel_name' => $hotel->name,
            'room_type'  => $validated['roomType'],
            'check_in'   => $validated['checkIn'],
            'check_out'  => $validated['checkOut'],
            'nights'     => $nights,
            'notes'      => $validated['notes'],
        ]);

        return back()->with('success', 'Accommodation request updated.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate(['status' => 'required|in:new,change,confirmed,cancelled']);

        $statusId = \App\Models\InvitationStatus::where('name', $validated['status'])->value('id');
        $accommodationRequest = AccommodationRequest::with('guest')->where('code', $id)->firstOrFail();
        $accommodationRequest->update(['status_id' => $statusId]);

        if ($validated['status'] === 'confirmed' && $accommodationRequest->guest?->email) {
            $event = GmsMockData::getEvent();
            \App\Services\Gms\ServiceConfirmationService::sendAccommodationConfirmation($accommodationRequest->guest, $event['name'] ?? '', [
                'code'     => $accommodationRequest->code,
                'hotel'    => $accommodationRequest->hotel_name,
                'roomType' => $accommodationRequest->room_type,
                'checkIn'  => $accommodationRequest->check_in?->format('d M Y'),
                'checkOut' => $accommodationRequest->check_out?->format('d M Y'),
                'nights'   => $accommodationRequest->nights,
            ]);
        }

        return back()->with('success', 'Status updated.');
    }

    public function destroy(string $id)
    {
        $accommodationRequest = AccommodationRequest::where('code', $id)->firstOrFail();
        $accommodationRequest->delete();

        return back()->with('success', 'Accommodation request deleted.');
    }

    public function bookGuestRequest(Request $request, string $guestRequestCode)
    {
        $guestRequest = AccommodationRequest::where('code', $guestRequestCode)->firstOrFail();

        if ($guestRequest->source !== 'portal' || $guestRequest->fulfilled_by_id !== null) {
            abort(422, 'This request has already been fulfilled.');
        }

        $validated = $request->validate([
            'guestId'  => 'required|exists:guests,id',
            'hotel'    => 'required|exists:hotels,id',
            'roomType' => 'required|string|max:80',
            'checkIn'  => 'required|date',
            'checkOut' => 'required|date|after:checkIn',
            'notes'    => 'nullable|string',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? 1;

        $lastCode = AccommodationRequest::where('event_id', $eventId)
            ->orderBy('id', 'desc')
            ->value('code');
        $nextNumber = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 1;
        $code = 'ACC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $checkIn = Carbon::parse($validated['checkIn']);
        $checkOut = Carbon::parse($validated['checkOut']);
        $nights = $checkIn->diffInDays($checkOut);

        $hotel = Hotel::findOrFail($validated['hotel']);

        $booking = AccommodationRequest::create([
            'event_id'             => $eventId,
            'guest_id'             => $validated['guestId'],
            'code'                 => $code,
            'status_id'            => \App\Models\InvitationStatus::where('name', 'pending')->value('id'),
            'hotel_id'             => $hotel->id,
            'hotel_code'           => $hotel->code,
            'hotel_name'           => $hotel->name,
            'room_type'            => $validated['roomType'],
            'check_in'             => $validated['checkIn'],
            'check_out'            => $validated['checkOut'],
            'nights'               => $nights,
            'notes'                => $validated['notes'],
            'source'               => 'manual',
            'initiated_by'         => 'team',
            'fulfills_request_id'  => $guestRequest->id,
        ]);

        $guestRequest->update(['fulfilled_by_id' => $booking->id]);

        $guest = Guest::find($validated['guestId']);
        if ($guest?->email) {
            $event = GmsMockData::getEvent();
            $portalUrl = null;
            try { $portalUrl = \App\Services\Gms\PortalTokenService::generateSignedUrl($guest); } catch (\Throwable $e) {}
            \App\Services\Gms\ServiceConfirmationService::sendServiceReview($guest, $event['name'] ?? '', 'Accommodation', [
                'Booking Code' => $code,
                'Hotel'        => $hotel->name,
                'Room Type'    => $validated['roomType'],
                'Check-in'     => $validated['checkIn'],
                'Check-out'    => $validated['checkOut'],
                'Nights'       => $nights,
            ], $portalUrl);
        }

        return back()->with('success', 'Accommodation booked from guest request.');
    }

    public function checkIn(string $id)
    {
        $req = AccommodationRequest::where('code', $id)->firstOrFail();
        $req->update(['checked_in_at' => now()]);
        return back()->with('success', 'Check-in confirmed.');
    }

    public function checkOut(string $id)
    {
        $req = AccommodationRequest::where('code', $id)->firstOrFail();
        $req->update(['checked_out_at' => now()]);
        return back()->with('success', 'Check-out confirmed.');
    }

    // ── Hotel CRUD ──

    public function storeHotel(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:120',
            'area'  => 'nullable|string|max:80',
            'stars' => 'required|integer|min:1|max:5',
        ]);

        $lastCode = Hotel::orderBy('id', 'desc')->value('code');
        $nextNum = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 1;
        $code = 'HOT-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT);

        Hotel::create([
            'code'  => $code,
            'name'  => $validated['name'],
            'area'  => $validated['area'],
            'stars' => $validated['stars'],
        ]);

        return back()->with('success', 'Hotel added.');
    }

    // ── Room block CRUD ──

    public function storeBlock(Request $request)
    {
        $validated = $request->validate([
            'hotelId'    => 'required|exists:hotels,id',
            'roomType'   => 'required|string|max:80',
            'rate'       => 'required|numeric|min:0',
            'currency'   => 'required|string|max:3',
            'checkIn'    => 'required|date',
            'checkOut'   => 'required|date|after:checkIn',
            'allotment'  => 'required|integer|min:1',
            'pickedUp'   => 'nullable|integer|min:0',
            'cutoffDate' => 'nullable|date',
            'notes'      => 'nullable|string',
        ]);

        $event = GmsMockData::getEvent();
        $hotel = Hotel::findOrFail($validated['hotelId']);

        RoomBlock::create([
            'event_id'    => $event['id'] ?? 1,
            'hotel_id'    => $hotel->id,
            'hotel_code'  => $hotel->code,
            'hotel_name'  => $hotel->name,
            'room_type'   => $validated['roomType'],
            'rate'        => $validated['rate'],
            'currency'    => $validated['currency'],
            'check_in'    => $validated['checkIn'],
            'check_out'   => $validated['checkOut'],
            'allotment'   => $validated['allotment'],
            'picked_up'   => $validated['pickedUp'] ?? 0,
            'cutoff_date' => $validated['cutoffDate'],
            'notes'       => $validated['notes'],
        ]);

        return back()->with('success', 'Room block created.');
    }

    public function updateBlock(Request $request, int $id)
    {
        $validated = $request->validate([
            'hotelId'    => 'required|exists:hotels,id',
            'roomType'   => 'required|string|max:80',
            'rate'       => 'required|numeric|min:0',
            'currency'   => 'required|string|max:3',
            'checkIn'    => 'required|date',
            'checkOut'   => 'required|date|after:checkIn',
            'allotment'  => 'required|integer|min:1',
            'pickedUp'   => 'nullable|integer|min:0',
            'cutoffDate' => 'nullable|date',
            'notes'      => 'nullable|string',
        ]);

        $block = RoomBlock::findOrFail($id);
        $hotel = Hotel::findOrFail($validated['hotelId']);

        $block->update([
            'hotel_id'    => $hotel->id,
            'hotel_code'  => $hotel->code,
            'hotel_name'  => $hotel->name,
            'room_type'   => $validated['roomType'],
            'rate'        => $validated['rate'],
            'currency'    => $validated['currency'],
            'check_in'    => $validated['checkIn'],
            'check_out'   => $validated['checkOut'],
            'allotment'   => $validated['allotment'],
            'picked_up'   => $validated['pickedUp'] ?? 0,
            'cutoff_date' => $validated['cutoffDate'],
            'notes'       => $validated['notes'],
        ]);

        return back()->with('success', 'Room block updated.');
    }

    public function updateBlockPickup(Request $request, int $id)
    {
        $validated = $request->validate([
            'pickedUp' => 'required|integer|min:0',
        ]);

        $block = RoomBlock::findOrFail($id);

        if ($validated['pickedUp'] > $block->allotment) {
            return back()->withErrors(['pickedUp' => 'Cannot exceed allotment.']);
        }

        $block->update(['picked_up' => $validated['pickedUp']]);

        return back()->with('success', 'Pickup updated.');
    }

    public function destroyBlock(int $id)
    {
        RoomBlock::findOrFail($id)->delete();

        return back()->with('success', 'Room block deleted.');
    }
}
