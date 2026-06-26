<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\TransportRequest;
use App\Models\VehicleBlock;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsTransportController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        $allRequests = TransportRequest::where('event_id', $eventId)
            ->with(['guest', 'status:id,name,label,color', 'fulfillsRequest'])
            ->orderBy('created_at', 'desc')
            ->get();

        $transform = function($r) {
            return [
                'id'                  => $r->code,
                'guestId'             => $r->guest_id,
                'guestName'           => $r->guest->name ?? 'Unknown',
                'status'              => $r->status->name ?? 'pending',
                'type'                => $r->type,
                'vehicle'             => $r->vehicle,
                'pickupLocation'      => $r->pickup_location,
                'dropoffLocation'     => $r->dropoff_location,
                'datetime'            => $r->datetime,
                'driver'              => $r->driver,
                'notes'               => $r->notes,
                'source'              => $r->source,
                'initiatedBy'         => $r->initiated_by,
                'fulfilledById'       => $r->fulfilled_by_id,
                'fulfillsRequestCode' => $r->fulfillsRequest?->code,
                'submitted'           => $r->created_at?->format('Y-m-d H:i'),
            ];
        };

        $guestRequests = $allRequests
            ->filter(fn($r) => $r->source === 'portal' && $r->initiated_by === 'guest')
            ->map($transform)->values();

        $requests = $allRequests
            ->filter(fn($r) => !($r->source === 'portal' && $r->initiated_by === 'guest'))
            ->map($transform)->values();

        $vehicleBlocks = VehicleBlock::query()
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('provider')
            ->orderBy('vehicle_type')
            ->get()
            ->map(fn(VehicleBlock $vb) => [
                'id'           => $vb->id,
                'provider'     => $vb->provider,
                'vehicleType'  => $vb->vehicle_type,
                'vehicleClass' => $vb->vehicle_class,
                'dailyRate'    => (float) $vb->daily_rate,
                'currency'     => $vb->currency,
                'startDate'    => $vb->start_date->format('Y-m-d'),
                'endDate'      => $vb->end_date->format('Y-m-d'),
                'fleetSize'    => $vb->fleet_size,
                'assigned'     => $vb->assigned,
                'cutoffDate'   => $vb->cutoff_date?->format('Y-m-d'),
                'notes'        => $vb->notes ?? '',
            ]);

        return Inertia::render('Gms/Transport/Index', [
            'requests'      => $requests,
            'guestRequests' => $guestRequests,
            'vehicleBlocks' => $vehicleBlocks,
            'guests'        => Guest::where('guestType', 'international')
                ->when($eventId, fn($q) => $q->where('event_id', $eventId))
                ->with(['tierInfo', 'group', 'invitation' => function($query) use ($eventId) {
                    $query->when($eventId, fn($q) => $q->where('event_id', $eventId))
                        ->with('status');
                }])
                ->orderBy('name')
                ->get()
                ->map(function($g) {
                    return [
                        'id' => $g->id,
                        'name' => $g->name,
                        'tier' => $g->tier,
                        'guestType' => $g->guestType,
                        'group' => $g->group->name ?? null,
                        'host' => $g->host,
                        'email' => $g->email,
                        'invitationStatus' => $g->invitation?->status?->name ?? null,
                        'hasConfirmedInvitation' => $g->invitation?->status?->name === 'confirmed',
                    ];
                }),
            'tiers'  => GmsMockData::getTiers(),
            'event'  => $event,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guestId'        => 'required|exists:guests,id',
            'type'           => 'required|string|max:60',
            'vehicle'        => 'nullable|string|max:80',
            'pickupLocation' => 'required|string|max:120',
            'dropoffLocation'=> 'required|string|max:120',
            'datetime'       => 'required|string',
            'notes'          => 'nullable|string',
        ]);

        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? 1;

        $lastCode = TransportRequest::where('event_id', $eventId)
            ->orderBy('id', 'desc')
            ->value('code');
        $nextNumber = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 1;
        $code = 'TRN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        TransportRequest::create([
            'event_id' => $eventId,
            'guest_id' => $validated['guestId'],
            'code' => $code,
            'status_id' => \App\Models\InvitationStatus::where('name', 'pending')->value('id'),
            'type' => $validated['type'],
            'vehicle' => $validated['vehicle'],
            'pickup_location' => $validated['pickupLocation'],
            'dropoff_location' => $validated['dropoffLocation'],
            'datetime' => $validated['datetime'],
            'driver' => 'TBD',
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Transport request created.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $request->validate(['status' => 'required|in:pending,confirmed,cancelled']);

        $statusId = \App\Models\InvitationStatus::where('name', $request->status)->value('id');
        $transportRequest = TransportRequest::with('guest')->where('code', $id)->firstOrFail();
        $transportRequest->update(['status_id' => $statusId]);

        if ($request->status === 'confirmed' && $transportRequest->guest?->email) {
            $event = GmsMockData::getEvent();
            \App\Services\Gms\ServiceConfirmationService::sendTransportConfirmation($transportRequest->guest, $event['name'] ?? '', [
                'code'     => $transportRequest->code,
                'type'     => $transportRequest->type,
                'vehicle'  => $transportRequest->vehicle,
                'pickup'   => $transportRequest->pickup_location,
                'dropoff'  => $transportRequest->dropoff_location,
                'datetime' => $transportRequest->datetime,
                'driver'   => $transportRequest->driver,
            ]);
        }

        return back()->with('success', 'Status updated.');
    }

    public function confirmCompletion(string $id)
    {
        $req = TransportRequest::where('code', $id)->firstOrFail();
        $req->update(['completed_at' => now()]);
        return back()->with('success', 'Service completion confirmed.');
    }

    public function destroy(string $id)
    {
        $transportRequest = TransportRequest::where('code', $id)->firstOrFail();
        $transportRequest->delete();

        return back()->with('success', 'Transport request deleted.');
    }

    public function bookGuestRequest(Request $request, string $guestRequestCode)
    {
        $guestRequest = TransportRequest::where('code', $guestRequestCode)->firstOrFail();

        if ($guestRequest->source !== 'portal' || $guestRequest->fulfilled_by_id !== null) {
            abort(422, 'This request has already been fulfilled.');
        }

        $validated = $request->validate([
            'guestId'        => 'required|exists:guests,id',
            'type'           => 'required|string|max:60',
            'vehicle'        => 'nullable|string|max:80',
            'pickupLocation' => 'required|string|max:120',
            'dropoffLocation'=> 'required|string|max:120',
            'datetime'       => 'required|string',
            'notes'          => 'nullable|string',
        ]);

        $lastCode = TransportRequest::where('event_id', $guestRequest->event_id)
            ->orderBy('id', 'desc')
            ->value('code');
        $nextNumber = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 1;
        $code = 'TRN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $booking = TransportRequest::create([
            'event_id'             => $guestRequest->event_id,
            'guest_id'             => $validated['guestId'],
            'code'                 => $code,
            'status_id'            => 'pending',
            'type'                 => $validated['type'],
            'vehicle'              => $validated['vehicle'],
            'pickup_location'      => $validated['pickupLocation'],
            'dropoff_location'     => $validated['dropoffLocation'],
            'datetime'             => $validated['datetime'],
            'driver'               => 'TBD',
            'notes'                => $validated['notes'],
            'source'               => 'manual',
            'initiated_by'         => 'team',
            'fulfills_request_id'  => $guestRequest->id,
        ]);

        $guestRequest->update(['fulfilled_by_id' => $booking->id]);

        return back()->with('success', 'Transport booked from guest request.');
    }

    // ── Vehicle block CRUD ──

    public function storeBlock(Request $request)
    {
        $validated = $request->validate([
            'provider'     => 'required|string|max:120',
            'vehicleType'  => 'required|string|max:80',
            'vehicleClass' => 'nullable|string|max:80',
            'dailyRate'    => 'required|numeric|min:0',
            'currency'     => 'required|string|max:3',
            'startDate'    => 'required|date',
            'endDate'      => 'required|date|after:startDate',
            'fleetSize'    => 'required|integer|min:1',
            'assigned'     => 'nullable|integer|min:0',
            'cutoffDate'   => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        $event = GmsMockData::getEvent();

        VehicleBlock::create([
            'event_id'      => $event['id'] ?? 1,
            'provider'      => $validated['provider'],
            'vehicle_type'  => $validated['vehicleType'],
            'vehicle_class' => $validated['vehicleClass'],
            'daily_rate'    => $validated['dailyRate'],
            'currency'      => $validated['currency'],
            'start_date'    => $validated['startDate'],
            'end_date'      => $validated['endDate'],
            'fleet_size'    => $validated['fleetSize'],
            'assigned'      => $validated['assigned'] ?? 0,
            'cutoff_date'   => $validated['cutoffDate'],
            'notes'         => $validated['notes'],
        ]);

        return back()->with('success', 'Vehicle block created.');
    }

    public function updateBlock(Request $request, int $id)
    {
        $validated = $request->validate([
            'provider'     => 'required|string|max:120',
            'vehicleType'  => 'required|string|max:80',
            'vehicleClass' => 'nullable|string|max:80',
            'dailyRate'    => 'required|numeric|min:0',
            'currency'     => 'required|string|max:3',
            'startDate'    => 'required|date',
            'endDate'      => 'required|date|after:startDate',
            'fleetSize'    => 'required|integer|min:1',
            'assigned'     => 'nullable|integer|min:0',
            'cutoffDate'   => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        $block = VehicleBlock::findOrFail($id);
        $block->update([
            'provider'      => $validated['provider'],
            'vehicle_type'  => $validated['vehicleType'],
            'vehicle_class' => $validated['vehicleClass'],
            'daily_rate'    => $validated['dailyRate'],
            'currency'      => $validated['currency'],
            'start_date'    => $validated['startDate'],
            'end_date'      => $validated['endDate'],
            'fleet_size'    => $validated['fleetSize'],
            'assigned'      => $validated['assigned'] ?? 0,
            'cutoff_date'   => $validated['cutoffDate'],
            'notes'         => $validated['notes'],
        ]);

        return back()->with('success', 'Vehicle block updated.');
    }

    public function updateBlockAssign(Request $request, int $id)
    {
        $validated = $request->validate([
            'assigned' => 'required|integer|min:0',
        ]);

        $block = VehicleBlock::findOrFail($id);

        if ($validated['assigned'] > $block->fleet_size) {
            return back()->withErrors(['assigned' => 'Cannot exceed fleet size.']);
        }

        $block->update(['assigned' => $validated['assigned']]);

        return back()->with('success', 'Assignment updated.');
    }

    public function destroyBlock(int $id)
    {
        VehicleBlock::findOrFail($id)->delete();

        return back()->with('success', 'Vehicle block deleted.');
    }
}
