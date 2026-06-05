<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsSeatingController extends Controller
{
    public function index()
    {
        $matches   = GmsMockData::getMatches();
        $templates = GmsMockData::getSeatingTemplates();
        $seeds     = GmsMockData::getMatchSeeds();
        $matchSeats = GmsMockData::getMatchSeats();
        $venues    = GmsMockData::getVenues();

        // Attach seat stats per match
        foreach ($matches as &$m) {
            $seats = $matchSeats[$m['id']] ?? [];
            $m['templateId']   = $seeds[$m['id']] ?? null;
            $m['seatStats']    = $this->calcStats($seats);
        }
        unset($m);

        return Inertia::render('Gms/Seating/Index', [
            'matches'          => $matches,
            'templates'        => $templates,
            'matchSeeds'       => $seeds,
            'matchSeats'       => $matchSeats,
            'venues'           => $venues,
            'guests'           => GmsMockData::getGuests(),
            'tiers'            => GmsMockData::getTiers(),
            'event'            => GmsMockData::getEvent(),
        ]);
    }

    public function assignSeat(Request $request, string $matchId, string $seatId)
    {
        $request->validate([
            'guestId' => 'required|string',
            'action'  => 'required|in:assign,reserve,release,issue_ticket,revoke_ticket,hide,unhide',
        ]);

        // TODO: persist to DB
        return response()->json(['success' => true]);
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'venueId' => 'required|string',
            'name'    => 'required|string|max:120',
            'blocks'  => 'required|array|min:1',
        ]);

        // TODO: persist to DB
        return back()->with('success', 'Template saved.');
    }

    private function calcStats(array $seats): array
    {
        $total    = 0;
        $assigned = 0;
        $reserved = 0;
        $ticket   = 0;
        foreach ($seats as $s) {
            if (!($s['hidden'] ?? false)) {
                $total++;
                if ($s['status'] === 'assigned') $assigned++;
                if ($s['status'] === 'reserved') $reserved++;
                if ($s['status'] === 'ticket')   $ticket++;
            }
        }
        return compact('total', 'assigned', 'reserved', 'ticket');
    }
}
