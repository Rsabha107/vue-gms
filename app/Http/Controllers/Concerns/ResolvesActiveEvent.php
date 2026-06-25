<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Event;

/**
 * Resolves the active/current event for GMS controllers.
 * Uses the session-stored event or falls back to the active event.
 */
trait ResolvesActiveEvent
{
    protected function activeEventId(): int
    {
        $sessionEventId = session('current_gms_event');
        if ($sessionEventId) {
            return (int) $sessionEventId;
        }
        
        $activeEvent = Event::where('active_flag', true)->first();
        return $activeEvent ? $activeEvent->id : 1;
    }

    protected function activeEvent(): ?Event
    {
        return Event::find($this->activeEventId());
    }
}
