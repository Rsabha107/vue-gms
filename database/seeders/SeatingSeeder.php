<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds seating templates and seats for the GMS seating module.
 * Creates templates for each venue with blocks, rows, and generates
 * seat instances for matches that have been assigned templates.
 */
class SeatingSeeder extends Seeder
{
    public function run(): void
    {
        // Get venues
        $lusail = DB::table('venues')->where('name', 'Lusail Stadium')->first();
        $albayt = DB::table('venues')->where('name', 'Al Bayt Stadium')->first();
        $edu = DB::table('venues')->where('name', 'Education City Stadium')->first();

        if (!$lusail || !$albayt || !$edu) {
            $this->command->error('Venues not found. Run VenueSeeder first.');
            return;
        }

        $now = now();

        // ---- Seating Templates ----
        
        // Lusail Stadium - Full VIP Tribune
        $lusailTemplateId = DB::table('seating_templates')->insertGetId([
            'venue_id' => $lusail->id,
            'name' => 'Lusail — Full VIP Tribune',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->createBlocks($lusailTemplateId, [
            ['code' => 'A', 'label' => 'Block A', 'tier' => 'VVIP', 'position' => 0, 'rows' => [
                ['label' => '1', 'seats' => 4,  'aisles' => [2], 'walkway' => false, 'position' => 0],
                ['label' => '2', 'seats' => 14, 'aisles' => [7], 'walkway' => false, 'position' => 1],
                ['label' => '3', 'seats' => 14, 'aisles' => [7], 'walkway' => false, 'position' => 2],
                ['label' => '4', 'seats' => 16, 'aisles' => [8], 'walkway' => true,  'position' => 3],
            ]],
            ['code' => 'B', 'label' => 'Block B', 'tier' => 'VVIP', 'position' => 1, 'rows' => [
                ['label' => '1', 'seats' => 6,  'aisles' => [3], 'walkway' => false, 'position' => 0],
                ['label' => '2', 'seats' => 12, 'aisles' => [6], 'walkway' => false, 'position' => 1],
                ['label' => '3', 'seats' => 14, 'aisles' => [7], 'walkway' => false, 'position' => 2],
            ]],
            ['code' => 'C', 'label' => 'Block C', 'tier' => 'VIP', 'position' => 2, 'rows' => [
                ['label' => '1', 'seats' => 12, 'aisles' => [6], 'walkway' => false, 'position' => 0],
                ['label' => '2', 'seats' => 16, 'aisles' => [8], 'walkway' => false, 'position' => 1],
                ['label' => '3', 'seats' => 18, 'aisles' => [6, 12], 'walkway' => false, 'position' => 2],
            ]],
        ], $now);

        // Al Bayt Stadium - Royal Tribune
        $albaytTemplateId = DB::table('seating_templates')->insertGetId([
            'venue_id' => $albayt->id,
            'name' => 'Al Bayt — Royal Tribune',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->createBlocks($albaytTemplateId, [
            ['code' => 'A', 'label' => 'Royal Box', 'tier' => 'VVIP', 'position' => 0, 'rows' => [
                ['label' => '1', 'seats' => 8,  'aisles' => [4], 'walkway' => false, 'position' => 0],
                ['label' => '2', 'seats' => 12, 'aisles' => [6], 'walkway' => false, 'position' => 1],
            ]],
            ['code' => 'B', 'label' => 'Tribune East', 'tier' => 'VIP', 'position' => 1, 'rows' => [
                ['label' => '1', 'seats' => 14, 'aisles' => [7], 'walkway' => false, 'position' => 0],
                ['label' => '2', 'seats' => 16, 'aisles' => [8], 'walkway' => true,  'position' => 1],
            ]],
        ], $now);

        // Education City Stadium - Compact VIP Section
        $eduTemplateId = DB::table('seating_templates')->insertGetId([
            'venue_id' => $edu->id,
            'name' => 'Education City — VIP Section',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->createBlocks($eduTemplateId, [
            ['code' => 'A', 'label' => 'Premium', 'tier' => 'VVIP', 'position' => 0, 'rows' => [
                ['label' => '1', 'seats' => 10, 'aisles' => [5], 'walkway' => false, 'position' => 0],
                ['label' => '2', 'seats' => 12, 'aisles' => [6], 'walkway' => true,  'position' => 1],
            ]],
            ['code' => 'B', 'label' => 'Standard', 'tier' => 'VIP', 'position' => 1, 'rows' => [
                ['label' => '1', 'seats' => 14, 'aisles' => [7], 'walkway' => false, 'position' => 0],
                ['label' => '2', 'seats' => 14, 'aisles' => [7], 'walkway' => false, 'position' => 1],
            ]],
        ], $now);

        $this->command->info('Seating templates created successfully.');
        $this->command->info("- Lusail Stadium: Template ID {$lusailTemplateId}");
        $this->command->info("- Al Bayt Stadium: Template ID {$albaytTemplateId}");
        $this->command->info("- Education City Stadium: Template ID {$eduTemplateId}");
    }

    /**
     * Create blocks and rows for a seating template
     */
    private function createBlocks(int $templateId, array $blocks, $now): void
    {
        foreach ($blocks as $block) {
            $blockId = DB::table('seating_blocks')->insertGetId([
                'seating_template_id' => $templateId,
                'code' => $block['code'],
                'label' => $block['label'],
                'tier' => $block['tier'],
                'position' => $block['position'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($block['rows'] as $row) {
                DB::table('seating_rows')->insert([
                    'seating_block_id' => $blockId,
                    'label' => $row['label'],
                    'seats' => $row['seats'],
                    'aisles' => json_encode($row['aisles']),
                    'walkway' => $row['walkway'],
                    'position' => $row['position'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
