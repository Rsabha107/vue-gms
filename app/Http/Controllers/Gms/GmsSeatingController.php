<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\SeatingTemplate;
use App\Models\SeatingBlock;
use App\Models\SeatingRow;
use App\Models\GameMatch;
use App\Models\Venue;
use App\Models\Guest;
use App\Models\ServiceLevel;
use App\Models\Event;
use App\Models\Seat;
use App\Services\Gms\GmsMockData;
use App\Services\Gms\SeatGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GmsSeatingController extends Controller
{
    public function index()
    {
        // Get current event
        $event = GmsMockData::getEvent();
        $eventId = $event['id'] ?? 1;

        // Fetch matches from database
        $matches = GameMatch::with(['venue', 'seatingTemplate'])
            ->where('event_id', $eventId)
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(function ($match) {
                // Format date as "Mon 10 Aug 2026"
                $formattedDate = $match->date ? 
                    ($match->day ? $match->day . ' ' : '') . 
                    $match->date->format('j M Y') : '';

                // Get seat stats
                $seatStats = $match->seatStats();

                return [
                    'id'         => $match->id,
                    'venueId'    => $match->venue_id,
                    'eventId'    => $match->event_id,
                    'name'       => $match->title,
                    'stage'      => $match->stage,
                    'label'      => $match->label,
                    'date'       => $formattedDate,
                    'time'       => $match->time,
                    'kickoff'    => $match->time,
                    'templateId' => $match->seating_template_id,
                    'seatStats'  => $seatStats,
                    'homeTeam'   => $match->team_a_name,
                    'awayTeam'   => $match->team_b_name,
                    'homeCode'   => $match->team_a_flag,
                    'awayCode'   => $match->team_b_flag,
                ];
            })
            ->values()
            ->all();
        
        // Fetch templates from database with blocks and rows
        $templates = SeatingTemplate::with(['blocks.rows', 'venue'])
            ->get()
            ->map(function ($tpl) {
                return [
                    'id'       => $tpl->id,
                    'venueId'  => $tpl->venue_id,
                    'name'     => $tpl->name,
                    'blocks'   => $tpl->toBlocksArray(),
                ];
            })
            ->values()
            ->all();
        
        // Fetch venues assigned to this event
        $venues = [];
        $eventModel = Event::find($eventId);
        if ($eventModel) {
            $venues = $eventModel->venues()
                ->orderBy('name')
                ->get()
                ->map(function ($venue) {
                    return [
                        'id'       => $venue->id,
                        'name'     => $venue->name,
                        'city'     => $venue->city,
                        'country'  => $venue->country,
                        'capacity' => $venue->capacity,
                        'type'     => $venue->type,
                    ];
                })
                ->values()
                ->all();
        }

        // Fetch guests from database
        $guests = Guest::where('event_id', $eventId)
            ->get()
            ->map(function ($guest) {
                return [
                    'id'           => $guest->id,
                    'name'         => $guest->name,
                    'title'        => $guest->title,
                    'email'        => $guest->email,
                    'phone'        => $guest->phone,
                    'nationality'  => $guest->nationality,
                    'tier'         => $guest->tier,
                    'group'        => $guest->group_id,
                    'host'         => $guest->host,
                    'hotel'        => $guest->hotel,
                    'status'       => $guest->status_id,
                    'companions'   => $guest->companions ?? 0,
                    'flag'         => '', // TODO: derive from nationality
                    'guestType'    => $guest->guestType,
                ];
            })
            ->values()
            ->all();

        // Fetch service levels (tiers) from database
        $tiers = ServiceLevel::all()
            ->map(function ($tier) {
                return [
                    'id'         => $tier->id,
                    'name'       => $tier->name,
                    'color'      => $tier->primary_color ?? '#8a1f3d',
                    'bgColor'    => $tier->bg_color ?? '#f5f0eb',
                    'facilities' => $tier->facilities ?? [],
                ];
            })
            ->values()
            ->all();

        // Build matchSeeds (templateId per match) and matchSeats (seats per match)
        $matchSeeds = [];
        $matchSeats = [];

        foreach ($matches as $match) {
            if ($match['templateId']) {
                $matchSeeds[$match['id']] = $match['templateId'];
                
                // Fetch seats for this match from database
                $seats = Seat::where('game_match_id', $match['id'])
                    ->get()
                    ->map(function ($seat) {
                        return [
                            'id'         => $seat->code,
                            'block'      => $seat->block_code,
                            'blockLabel' => $seat->block_code, // TODO: fetch from template
                            'row'        => $seat->row_label,
                            'rowLabel'   => $seat->row_label,
                            'col'        => $seat->col,
                            'status'     => $seat->status,
                            'guestId'    => $seat->guest_id,
                            'resLabel'   => $seat->res_label,
                            'hidden'     => $seat->hidden,
                        ];
                    })
                    ->values()
                    ->all();
                
                $matchSeats[$match['id']] = $seats;
            }
        }

        return Inertia::render('Gms/Seating/Index', [
            'matches'          => $matches,
            'templates'        => $templates,
            'matchSeeds'       => $matchSeeds,
            'matchSeats'       => $matchSeats,
            'venues'           => $venues,
            'guests'           => $guests,
            'tiers'            => $tiers,
            'event'            => $event,
        ]);
    }

    public function assignSeat(Request $request, string $matchId, string $seatId)
    {
        $validated = $request->validate([
            'guestId' => 'nullable|integer|exists:guests,id',
            'action'  => 'required|in:assign,reserve,release,issue_ticket,revoke_ticket,hide,unhide,unassign',
            'resLabel' => 'nullable|string',
        ]);

        $seat = Seat::where('game_match_id', $matchId)
            ->where('code', $seatId)
            ->firstOrFail();

        $action = $validated['action'];

        switch ($action) {
            case 'assign':
                $seat->status = Seat::ASSIGNED;
                $seat->guest_id = $validated['guestId'];
                $seat->res_label = null;
                break;

            case 'unassign':
                $seat->status = Seat::AVAILABLE;
                $seat->guest_id = null;
                $seat->res_label = null;
                break;

            case 'reserve':
                $seat->status = Seat::RESERVED;
                $seat->guest_id = null;
                $seat->res_label = $validated['resLabel'] ?? 'Reserved';
                break;

            case 'release':
                $seat->status = Seat::AVAILABLE;
                $seat->guest_id = null;
                $seat->res_label = null;
                break;

            case 'issue_ticket':
                $seat->status = Seat::TICKET;
                // Keep existing guest_id
                break;

            case 'revoke_ticket':
                $seat->status = Seat::ASSIGNED;
                // Keep existing guest_id
                break;

            case 'hide':
                $seat->hidden = true;
                break;

            case 'unhide':
                $seat->hidden = false;
                break;
        }

        $seat->save();

        return response()->json(['success' => true, 'seat' => $seat->toMapArray()]);
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'venueId' => 'required|integer|exists:venues,id',
            'name'    => 'required|string|max:120',
            'blocks'  => 'required|array|min:1',
            'blocks.*.id'    => 'required|string',
            'blocks.*.label' => 'required|string',
            'blocks.*.tier'  => 'nullable|string',
            'blocks.*.rows'  => 'required|array|min:1',
            'blocks.*.rows.*.label'   => 'required',
            'blocks.*.rows.*.seats'   => 'required|integer|min:1',
            'blocks.*.rows.*.aisles'  => 'nullable|array',
            'blocks.*.rows.*.walkway' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $template = SeatingTemplate::create([
                'venue_id' => $validated['venueId'],
                'name'     => $validated['name'],
            ]);

            foreach ($validated['blocks'] as $position => $blockData) {
                $block = SeatingBlock::create([
                    'seating_template_id' => $template->id,
                    'code'                => $blockData['id'],
                    'label'               => $blockData['label'],
                    'tier'                => $blockData['tier'] ?? null,
                    'position'            => $position,
                ]);

                foreach ($blockData['rows'] as $rowPosition => $rowData) {
                    SeatingRow::create([
                        'seating_block_id' => $block->id,
                        'label'            => $rowData['label'],
                        'seats'            => $rowData['seats'],
                        'aisles'           => $rowData['aisles'] ?? [],
                        'walkway'          => $rowData['walkway'] ?? false,
                        'position'         => $rowPosition,
                    ]);
                }
            }
        });

        return back()->with('success', 'Template created successfully.');
    }

    public function updateTemplate(Request $request, $id)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:120',
            'blocks' => 'required|array|min:1',
            'blocks.*.id'    => 'required|string',
            'blocks.*.label' => 'required|string',
            'blocks.*.tier'  => 'nullable|string',
            'blocks.*.rows'  => 'required|array|min:1',
            'blocks.*.rows.*.label'   => 'required',
            'blocks.*.rows.*.seats'   => 'required|integer|min:1',
            'blocks.*.rows.*.aisles'  => 'nullable|array',
            'blocks.*.rows.*.walkway' => 'nullable|boolean',
        ]);

        $template = SeatingTemplate::findOrFail($id);

        DB::transaction(function () use ($template, $validated) {
            $template->update(['name' => $validated['name']]);

            // Delete existing blocks and rows
            $template->blocks()->each(function ($block) {
                $block->rows()->delete();
            });
            $template->blocks()->delete();

            // Recreate blocks and rows
            foreach ($validated['blocks'] as $position => $blockData) {
                $block = SeatingBlock::create([
                    'seating_template_id' => $template->id,
                    'code'                => $blockData['id'],
                    'label'               => $blockData['label'],
                    'tier'                => $blockData['tier'] ?? null,
                    'position'            => $position,
                ]);

                foreach ($blockData['rows'] as $rowPosition => $rowData) {
                    SeatingRow::create([
                        'seating_block_id' => $block->id,
                        'label'            => $rowData['label'],
                        'seats'            => $rowData['seats'],
                        'aisles'           => $rowData['aisles'] ?? [],
                        'walkway'          => $rowData['walkway'] ?? false,
                        'position'         => $rowPosition,
                    ]);
                }
            }
        });

        // Regenerate seats for all matches using this template
        $matchesUsingTemplate = GameMatch::where('seating_template_id', $template->id)->get();
        $template->refresh()->load(['blocks.rows']);
        
        foreach ($matchesUsingTemplate as $match) {
            SeatGeneratorService::regenerateSeatsForMatch($match, $template);
        }

        return back()->with('success', 'Template updated successfully.');
    }

    public function duplicateTemplate($id)
    {
        $original = SeatingTemplate::with(['blocks.rows'])->findOrFail($id);

        DB::transaction(function () use ($original) {
            $duplicate = SeatingTemplate::create([
                'venue_id' => $original->venue_id,
                'name'     => $original->name . ' (Copy)',
            ]);

            foreach ($original->blocks as $originalBlock) {
                $newBlock = SeatingBlock::create([
                    'seating_template_id' => $duplicate->id,
                    'code'                => $originalBlock->code,
                    'label'               => $originalBlock->label,
                    'tier'                => $originalBlock->tier,
                    'position'            => $originalBlock->position,
                ]);

                foreach ($originalBlock->rows as $originalRow) {
                    SeatingRow::create([
                        'seating_block_id' => $newBlock->id,
                        'label'            => $originalRow->label,
                        'seats'            => $originalRow->seats,
                        'aisles'           => $originalRow->aisles,
                        'walkway'          => $originalRow->walkway,
                        'position'         => $originalRow->position,
                    ]);
                }
            }
        });

        return back()->with('success', 'Template duplicated successfully.');
    }

    public function destroyTemplate($id)
    {
        $template = SeatingTemplate::findOrFail($id);

        // Check if template is in use
        $matchCount = $template->matches()->count();
        if ($matchCount > 0) {
            return back()->withErrors(['error' => "Cannot delete: template is used by {$matchCount} match" . ($matchCount === 1 ? '' : 'es') . '.']);
        }

        DB::transaction(function () use ($template) {
            // Delete all related blocks and rows
            $template->blocks()->each(function ($block) {
                $block->rows()->delete();
            });
            $template->blocks()->delete();
            $template->delete();
        });

        return back()->with('success', 'Template deleted successfully.');
    }

    public function assignTemplateToMatch(Request $request, $matchId)
    {
        $validated = $request->validate([
            'templateId' => 'required|integer|exists:seating_templates,id',
        ]);

        $match = GameMatch::findOrFail($matchId);
        $template = SeatingTemplate::with(['blocks.rows'])->findOrFail($validated['templateId']);

        // Delete any existing seats for this match
        $match->seats()->delete();

        // Assign template and generate seats
        $seatCount = SeatGeneratorService::assignTemplateToMatch($match, $template);

        return back()->with('success', "Template assigned. Generated {$seatCount} seats.");
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
