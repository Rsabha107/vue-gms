<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsInvitationController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/Invitations/Index', [
            'guests'         => GmsMockData::getGuests(),
            'tiers'          => GmsMockData::getTiers(),
            'emailTemplates' => GmsMockData::getEmailTemplates(),
            'event'          => GmsMockData::getEvent(),
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
