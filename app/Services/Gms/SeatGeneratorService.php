<?php

namespace App\Services\Gms;

use App\Models\Seat;
use App\Models\SeatingTemplate;
use App\Models\GameMatch;
use Illuminate\Support\Facades\DB;

class SeatGeneratorService
{
    /**
     * Generate seats for a match from a seating template.
     * Creates one Seat row per template seat, all starting as 'available'.
     * 
     * @param GameMatch $match
     * @param SeatingTemplate $template
     * @return int Number of seats created
     */
    public static function generateSeatsForMatch(GameMatch $match, SeatingTemplate $template): int
    {
        $seats = [];
        $template->loadMissing(['blocks.rows']);

        foreach ($template->blocks as $block) {
            foreach ($block->rows as $row) {
                $rowLabel = (string) $row->label;
                $rowId = str_pad($rowLabel, 2, '0', STR_PAD_LEFT);

                for ($col = 1; $col <= $row->seats; $col++) {
                    $colId = str_pad((string) $col, 2, '0', STR_PAD_LEFT);
                    $code = "{$block->code}-{$rowId}-{$colId}";

                    $seats[] = [
                        'game_match_id'        => $match->id,
                        'seating_template_id'  => $template->id,
                        'code'                 => $code,
                        'block_code'           => $block->code,
                        'row_label'            => $rowLabel,
                        'col'                  => $col,
                        'status'               => Seat::AVAILABLE,
                        'guest_id'             => null,
                        'res_label'            => null,
                        'hidden'               => false,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }
            }
        }

        if (!empty($seats)) {
            Seat::insert($seats);
        }

        return count($seats);
    }

    /**
     * Regenerate seats for a match after template changes.
     * Preserves existing assignments/reservations where seat codes still exist.
     * 
     * @param GameMatch $match
     * @param SeatingTemplate $template
     * @return array ['created' => int, 'preserved' => int, 'removed' => int]
     */
    public static function regenerateSeatsForMatch(GameMatch $match, SeatingTemplate $template): array
    {
        return DB::transaction(function () use ($match, $template) {
            // Fetch current seat assignments
            $existingSeats = $match->seats()
                ->whereNotNull('guest_id')
                ->orWhere('status', '!=', Seat::AVAILABLE)
                ->get()
                ->keyBy('code');

            // Delete all existing seats
            $removedCount = $match->seats()->delete();

            // Generate new seats
            $seats = [];
            $template->loadMissing(['blocks.rows']);
            $preservedCount = 0;

            foreach ($template->blocks as $block) {
                foreach ($block->rows as $row) {
                    $rowLabel = (string) $row->label;
                    $rowId = str_pad($rowLabel, 2, '0', STR_PAD_LEFT);

                    for ($col = 1; $col <= $row->seats; $col++) {
                        $colId = str_pad((string) $col, 2, '0', STR_PAD_LEFT);
                        $code = "{$block->code}-{$rowId}-{$colId}";

                        $existingSeat = $existingSeats->get($code);

                        if ($existingSeat) {
                            // Preserve assignment
                            $seats[] = [
                                'game_match_id'        => $match->id,
                                'seating_template_id'  => $template->id,
                                'code'                 => $code,
                                'block_code'           => $block->code,
                                'row_label'            => $rowLabel,
                                'col'                  => $col,
                                'status'               => $existingSeat->status,
                                'guest_id'             => $existingSeat->guest_id,
                                'res_label'            => $existingSeat->res_label,
                                'hidden'               => $existingSeat->hidden,
                                'created_at'           => now(),
                                'updated_at'           => now(),
                            ];
                            $preservedCount++;
                        } else {
                            // New seat
                            $seats[] = [
                                'game_match_id'        => $match->id,
                                'seating_template_id'  => $template->id,
                                'code'                 => $code,
                                'block_code'           => $block->code,
                                'row_label'            => $rowLabel,
                                'col'                  => $col,
                                'status'               => Seat::AVAILABLE,
                                'guest_id'             => null,
                                'res_label'            => null,
                                'hidden'               => false,
                                'created_at'           => now(),
                                'updated_at'           => now(),
                            ];
                        }
                    }
                }
            }

            if (!empty($seats)) {
                Seat::insert($seats);
            }

            return [
                'created'   => count($seats),
                'preserved' => $preservedCount,
                'removed'   => $removedCount - $preservedCount,
            ];
        });
    }

    /**
     * Assign a template to a match and generate its seats.
     * 
     * @param GameMatch $match
     * @param SeatingTemplate $template
     * @return int Number of seats created
     */
    public static function assignTemplateToMatch(GameMatch $match, SeatingTemplate $template): int
    {
        // Update match to reference the template
        $match->seating_template_id = $template->id;
        $match->save();

        // Generate seats
        return self::generateSeatsForMatch($match, $template);
    }
}
