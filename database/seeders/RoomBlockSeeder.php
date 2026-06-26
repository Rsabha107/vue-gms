<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Hotel;
use App\Models\RoomBlock;
use Illuminate\Database\Seeder;

class RoomBlockSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::where('active_flag', true)->first();

        if (!$event) {
            $this->command->warn('No active event found. Run EventSeeder first.');
            return;
        }

        $blocks = [
            [
                'hotel_code'  => 'HOT-01',
                'room_type'   => 'Deluxe King',
                'rate'        => 850,
                'currency'    => 'QAR',
                'check_in'    => '2026-08-09',
                'check_out'   => '2026-08-23',
                'allotment'   => 80,
                'picked_up'   => 62,
                'cutoff_date' => '2026-07-25',
                'notes'       => 'Federation master block.',
            ],
            [
                'hotel_code'  => 'HOT-01',
                'room_type'   => 'Executive Suite',
                'rate'        => 1800,
                'currency'    => 'QAR',
                'check_in'    => '2026-08-09',
                'check_out'   => '2026-08-23',
                'allotment'   => 18,
                'picked_up'   => 18,
                'cutoff_date' => '2026-07-25',
                'notes'       => null,
            ],
            [
                'hotel_code'  => 'HOT-02',
                'room_type'   => 'Premier Room',
                'rate'        => 920,
                'currency'    => 'QAR',
                'check_in'    => '2026-08-10',
                'check_out'   => '2026-08-22',
                'allotment'   => 120,
                'picked_up'   => 74,
                'cutoff_date' => '2026-07-20',
                'notes'       => null,
            ],
            [
                'hotel_code'  => 'HOT-03',
                'room_type'   => 'Twin Room',
                'rate'        => 620,
                'currency'    => 'QAR',
                'check_in'    => '2026-08-11',
                'check_out'   => '2026-08-20',
                'allotment'   => 60,
                'picked_up'   => 21,
                'cutoff_date' => '2026-07-15',
                'notes'       => null,
            ],
            [
                'hotel_code'  => 'HOT-04',
                'room_type'   => 'Suite',
                'rate'        => 1100,
                'currency'    => 'QAR',
                'check_in'    => '2026-08-09',
                'check_out'   => '2026-08-23',
                'allotment'   => 30,
                'picked_up'   => 9,
                'cutoff_date' => '2026-07-28',
                'notes'       => null,
            ],
        ];

        $this->command->info('Creating room blocks...');

        foreach ($blocks as $block) {
            $hotel = Hotel::where('code', $block['hotel_code'])->first();

            if (!$hotel) {
                $this->command->warn("Hotel {$block['hotel_code']} not found. Skipping.");
                continue;
            }

            RoomBlock::create(array_merge($block, [
                'event_id'   => $event->id,
                'hotel_id'   => $hotel->id,
                'hotel_name' => $hotel->name,
            ]));

            $this->command->info("✓ {$hotel->name} — {$block['room_type']} ({$block['allotment']} rooms)");
        }

        $this->command->info('Room blocks seeded successfully!');
    }
}
