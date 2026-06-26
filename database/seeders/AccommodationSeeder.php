<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Event;
use App\Models\Hotel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccommodationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the active event (Doha Cup '26)
        $event = Event::where('active_flag', true)->first();
        
        if (!$event) {
            $this->command->warn('No active event found. Run EventSeeder first.');
            return;
        }

        $now = now();
        
        $accommodations = [
            [
                'guest_name' => 'Emmanuel Macron',
                'code' => 'ACC-001',
                'status_id' => 'confirmed',
                'hotel_code' => 'HOT-01',
                'hotel_name' => 'Four Seasons Doha',
                'room_type' => 'Presidential Suite',
                'check_in' => '2026-01-14',
                'check_out' => '2026-01-20',
                'nights' => 6,
                'notes' => 'Private entrance requested',
            ],
            [
                'guest_name' => 'Prince William',
                'code' => 'ACC-002',
                'status_id' => 'confirmed',
                'hotel_code' => 'HOT-03',
                'hotel_name' => 'St. Regis Doha',
                'room_type' => 'Royal Suite',
                'check_in' => '2026-01-14',
                'check_out' => '2026-01-16',
                'nights' => 2,
                'notes' => 'Security sweep required prior to arrival',
            ],
            [
                'guest_name' => 'Olaf Scholz',
                'code' => 'ACC-003',
                'status_id' => 'confirmed',
                'hotel_code' => 'HOT-05',
                'hotel_name' => 'Marsa Malaz Kempinski',
                'room_type' => 'Diplomatic Suite',
                'check_in' => '2026-01-15',
                'check_out' => '2026-01-17',
                'nights' => 2,
                'notes' => null,
            ],
            [
                'guest_name' => 'Arsène Wenger',
                'code' => 'ACC-004',
                'status_id' => 'new',
                'hotel_code' => 'HOT-06',
                'hotel_name' => 'InterContinental Doha',
                'room_type' => 'Executive Suite',
                'check_in' => '2026-01-14',
                'check_out' => '2026-01-19',
                'nights' => 5,
                'notes' => 'Awaiting FIFA allocation confirmation',
            ],
            [
                'guest_name' => 'Kylian Mbappé',
                'code' => 'ACC-005',
                'status_id' => 'confirmed',
                'hotel_code' => 'HOT-07',
                'hotel_name' => 'Waldorf Astoria Lusail',
                'room_type' => 'Deluxe Suite',
                'check_in' => '2026-01-17',
                'check_out' => '2026-01-20',
                'nights' => 3,
                'notes' => null,
            ],
            [
                'guest_name' => 'Cristiano Ronaldo',
                'code' => 'ACC-006',
                'status_id' => 'change',
                'hotel_code' => 'HOT-07',
                'hotel_name' => 'Waldorf Astoria Lusail',
                'room_type' => 'Penthouse Suite',
                'check_in' => '2026-01-17',
                'check_out' => '2026-01-19',
                'nights' => 2,
                'notes' => 'Room upgrade requested',
            ],
            [
                'guest_name' => 'Pedro Sánchez',
                'code' => 'ACC-007',
                'status_id' => 'new',
                'hotel_code' => 'HOT-01',
                'hotel_name' => 'Four Seasons Doha',
                'room_type' => 'Diplomatic Suite',
                'check_in' => '2026-01-14',
                'check_out' => '2026-01-16',
                'nights' => 2,
                'notes' => null,
            ],
            [
                'guest_name' => 'Prince Albert II',
                'code' => 'ACC-008',
                'status_id' => 'confirmed',
                'hotel_code' => 'HOT-03',
                'hotel_name' => 'St. Regis Doha',
                'room_type' => 'Royal Suite',
                'check_in' => '2026-01-14',
                'check_out' => '2026-01-16',
                'nights' => 2,
                'notes' => null,
            ],
        ];

        $this->command->info('Creating accommodation requests...');

        foreach ($accommodations as $acc) {
            // Find guest by name
            $guest = Guest::where('name', 'LIKE', '%' . $acc['guest_name'] . '%')->first();
            
            if (!$guest) {
                $this->command->warn("Guest '{$acc['guest_name']}' not found. Skipping {$acc['code']}.");
                continue;
            }

            $hotel = Hotel::where('code', $acc['hotel_code'])->first();

            DB::table('accommodation_requests')->insert([
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'code' => $acc['code'],
                'status_id' => $acc['status_id'],
                'hotel_id' => $hotel?->id,
                'hotel_code' => $acc['hotel_code'],
                'hotel_name' => $acc['hotel_name'],
                'room_type' => $acc['room_type'],
                'check_in' => $acc['check_in'],
                'check_out' => $acc['check_out'],
                'nights' => $acc['nights'],
                'notes' => $acc['notes'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->command->info("✓ Created {$acc['code']} for {$acc['guest_name']}");
        }

        $this->command->info('Accommodation requests seeded successfully!');
    }
}
