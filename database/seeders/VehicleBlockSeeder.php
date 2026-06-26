<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\VehicleBlock;
use Illuminate\Database\Seeder;

class VehicleBlockSeeder extends Seeder
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
                'provider'      => 'Al Maha Limousines',
                'vehicle_type'  => 'Mercedes S-Class',
                'vehicle_class' => 'VIP Sedan',
                'daily_rate'    => 1200,
                'currency'      => 'QAR',
                'start_date'    => '2026-08-09',
                'end_date'      => '2026-08-23',
                'fleet_size'    => 20,
                'assigned'      => 14,
                'cutoff_date'   => '2026-07-25',
                'notes'         => 'Primary VVIP fleet.',
            ],
            [
                'provider'      => 'Al Maha Limousines',
                'vehicle_type'  => 'Range Rover Autobiography',
                'vehicle_class' => 'VIP SUV',
                'daily_rate'    => 1600,
                'currency'      => 'QAR',
                'start_date'    => '2026-08-09',
                'end_date'      => '2026-08-23',
                'fleet_size'    => 8,
                'assigned'      => 8,
                'cutoff_date'   => '2026-07-25',
                'notes'         => null,
            ],
            [
                'provider'      => 'Karwa Premium',
                'vehicle_type'  => 'Mercedes V-Class',
                'vehicle_class' => 'Group Van',
                'daily_rate'    => 900,
                'currency'      => 'QAR',
                'start_date'    => '2026-08-10',
                'end_date'      => '2026-08-22',
                'fleet_size'    => 15,
                'assigned'      => 6,
                'cutoff_date'   => '2026-07-20',
                'notes'         => null,
            ],
            [
                'provider'      => 'Karwa Premium',
                'vehicle_type'  => 'Toyota Coaster',
                'vehicle_class' => 'Minibus',
                'daily_rate'    => 650,
                'currency'      => 'QAR',
                'start_date'    => '2026-08-11',
                'end_date'      => '2026-08-20',
                'fleet_size'    => 10,
                'assigned'      => 3,
                'cutoff_date'   => '2026-07-15',
                'notes'         => null,
            ],
            [
                'provider'      => 'Qatar Executive',
                'vehicle_type'  => 'BMW 7 Series',
                'vehicle_class' => 'Executive Sedan',
                'daily_rate'    => 1100,
                'currency'      => 'QAR',
                'start_date'    => '2026-08-09',
                'end_date'      => '2026-08-23',
                'fleet_size'    => 12,
                'assigned'      => 5,
                'cutoff_date'   => '2026-07-28',
                'notes'         => null,
            ],
        ];

        $this->command->info('Creating vehicle blocks...');

        foreach ($blocks as $block) {
            VehicleBlock::create(array_merge($block, [
                'event_id' => $event->id,
            ]));

            $this->command->info("✓ {$block['provider']} — {$block['vehicle_type']} ({$block['fleet_size']} vehicles)");
        }

        $this->command->info('Vehicle blocks seeded successfully!');
    }
}
