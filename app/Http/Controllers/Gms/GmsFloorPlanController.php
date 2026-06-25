<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesActiveEvent;
use App\Models\Floorplan;
use App\Models\Guest;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Floor plans (banquet / gala TABLE seating). The interactive canvas is
 * self-contained; the whole plan persists as one JSON document per event
 * (server-of-truth). Chairs store guest IDs, so the live roster is sent
 * alongside and missing IDs render empty.
 */
class GmsFloorPlanController extends Controller
{
    use ResolvesActiveEvent;

    public function index(): Response
    {
        $eventId = $this->activeEventId();
        
        $plan = Floorplan::firstOrCreate(
            ['event_id' => $eventId],
            ['name' => 'Gala Dinner', 'data' => ['items' => []]],
        );

        return Inertia::render('Gms/FloorPlans/Index', [
            'plan'   => [
                'id' => $plan->id,
                'name' => $plan->name,
                'data' => $plan->data ?? ['items' => []]
            ],
            'guests' => Guest::orderBy('name')->get([
                'id', 'name', 'title', 'tier', 'group_id', 'nationality', 'companions',
            ]),
            'event'  => GmsMockData::getEvent(),
        ]);
    }

    /**
     * Persist the full plan JSON.
     * POST /gms/floorplans/{plan}
     */
    public function save(Request $request, Floorplan $plan): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'data' => ['required', 'array'],
        ]);

        $plan->update([
            'name' => $data['name'] ?? $plan->name,
            'data' => $data['data'],
        ]);

        return response()->json([
            'ok' => true,
            'savedAt' => now()->toIso8601String()
        ]);
    }
}
