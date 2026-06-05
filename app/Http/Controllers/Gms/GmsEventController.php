<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;

class GmsEventController extends Controller
{
    /**
     * Switch the current event
     */
    public function switch(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id'
        ]);

        session(['gms_current_event_id' => $request->event_id]);

        return back()->with('success', 'Event switched successfully');
    }
}
