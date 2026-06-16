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
        $guests = Guest::with(['status', 'group'])
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->orderBy('reference_number')
            ->get()
            ->map(function ($guest) {
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
