<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsInvitationController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? null;

        // Fetch guests for the current event
        $guests = Guest::with(['status', 'group', 'flightRequests.legs', 'accommodationRequests'])
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('reference_number')
            ->get()
            ->map(function ($guest) {
                // Get most recent flight request (prioritize confirmed)
                $flight = $guest->flightRequests
                    ->sortByDesc(fn($f) => [$f->status === 'confirmed' ? 1 : 0, $f->created_at])
                    ->first();
                
                // Get first accommodation request (prioritize confirmed)
                $accommodation = $guest->accommodationRequests
                    ->sortByDesc(fn($a) => [$a->status_id === 'confirmed' ? 1 : 0, $a->created_at])
                    ->first();
                
                // Build flight display string
                $flightDisplay = null;
                if ($flight) {
                    $inbound = $flight->legs->where('dir', 'Inbound')->first();
                    $flightDisplay = $inbound ? $inbound->flight_no : $flight->code;
                }
                
                return [
                    'id' => $guest->id,
                    'reference_number' => $guest->reference_number,
                    'name' => $guest->name,
                    'firstName' => $guest->firstName,
                    'lastName' => $guest->lastName,
                    'title' => $guest->title,
                    'tier' => $guest->tier,
                    'group' => $guest->group?->name,
                    'nationality' => $guest->nationality,
                    'status' => $guest->status?->name ?? 'pending',
                    'email' => $guest->email,
                    'phone' => $guest->phone,
                    'flight' => $flightDisplay,
                    'flightStatus' => $flight ? $flight->status : null,
                    'hotel' => $accommodation ? $accommodation->hotel_name : null,
                    'hotelStatus' => $accommodation ? $accommodation->status_id : null,
                ];
            })
            ->toArray();

        return Inertia::render('Gms/Invitations/Index', [
            'guests'         => $guests,
            'tiers'          => GmsMockData::getTiers(),
            'emailTemplates' => GmsMockData::getEmailTemplates(),
            'event'          => $event,
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'guestIds'   => 'required|array|min:1',
            'templateId' => 'required|string',
        ]);

        // TODO: dispatch email jobs
        return back()->with('success', count($request->guestIds) . ' invitation(s) sent.');
    }
}
