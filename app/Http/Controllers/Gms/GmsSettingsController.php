<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;

class GmsSettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        $event = GmsMockData::getEvent();

        return inertia('Gms/Settings/Index', [
            'user'           => auth()->user(),
            'event'          => $event,
            'teamUsers'      => User::orderBy('name')->get(),
            'emailTemplates' => GmsMockData::getEmailTemplates(),
            'portalSettings' => [
                'enabled'   => $event['portal_enabled'] ?? false,
                'authMode'  => $event['portal_auth_mode'] ?? 'signed_url',
            ],
        ]);
    }

    /**
     * Update portal settings for the current event
     */
    public function updatePortalSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled'   => 'required|boolean',
            'authMode'  => 'required|in:signed_url,totp,magic_link,full_auth',
        ]);

        $eventData = GmsMockData::getEvent();
        $event = Event::find($eventData['id']);
        
        if (!$event) {
            return back()->with('error', 'Event not found');
        }

        $event->portal_enabled = $validated['enabled'];
        $event->portal_auth_mode = $validated['authMode'];
        $event->save();

        return back()->with('success', 'Portal settings updated');
    }
}
