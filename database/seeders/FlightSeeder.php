<?php

namespace Database\Seeders;

use App\Models\Guest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get international guests who would need flights
        $guests = Guest::where('guestType', 'international')->get();
        
        if ($guests->isEmpty()) {
            $this->command->warn('No international guests found. Run GuestSeeder first.');
            return;
        }

        $flightRequests = [
            // Emmanuel Macron - France
            [
                'guest_name' => 'Emmanuel Macron',
                'code' => 'FL-001',
                'status' => 'confirmed',
                'ref' => 'QR8X4M2N',
                'pax' => 2,
                'requested_at' => '2026-11-01 09:30',
                'note' => 'Presidential delegation',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR38',
                        'from_code' => 'CDG',
                        'from_city' => 'Paris',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-02',
                        'dep' => '14:20',
                        'arr' => '22:35',
                        'cls' => 'Business',
                        'dur' => '6h 15m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR39',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'CDG',
                        'to_city' => 'Paris',
                        'date' => '2026-12-10',
                        'dep' => '08:15',
                        'arr' => '13:45',
                        'cls' => 'Business',
                        'dur' => '7h 30m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // Gianni Infantino - Switzerland
            [
                'guest_name' => 'Gianni Infantino',
                'code' => 'FL-002',
                'status' => 'confirmed',
                'ref' => 'QR5P9K7L',
                'pax' => 1,
                'requested_at' => '2026-10-15 14:20',
                'note' => 'FIFA President - VIP services required',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR94',
                        'from_code' => 'ZRH',
                        'from_city' => 'Zurich',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-01',
                        'dep' => '09:45',
                        'arr' => '17:20',
                        'cls' => 'First',
                        'dur' => '5h 35m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR95',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'ZRH',
                        'to_city' => 'Zurich',
                        'date' => '2026-12-15',
                        'dep' => '02:15',
                        'arr' => '07:05',
                        'cls' => 'First',
                        'dur' => '6h 50m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // Prince William - UK
            [
                'guest_name' => 'Prince William',
                'code' => 'FL-003',
                'status' => 'confirmed',
                'ref' => 'QR2H8V4C',
                'pax' => 4,
                'requested_at' => '2026-10-20 11:15',
                'note' => 'Royal delegation with security detail',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR2',
                        'from_code' => 'LHR',
                        'from_city' => 'London',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-03',
                        'dep' => '20:35',
                        'arr' => '05:45',
                        'cls' => 'First',
                        'dur' => '6h 10m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR3',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'LHR',
                        'to_city' => 'London',
                        'date' => '2026-12-08',
                        'dep' => '07:55',
                        'arr' => '12:35',
                        'cls' => 'First',
                        'dur' => '7h 40m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // Sheikh Mohammed bin Rashid - UAE
            [
                'guest_name' => 'Sheikh Mohammed bin Rashid Al Maktoum',
                'code' => 'FL-004',
                'status' => 'confirmed',
                'ref' => 'EK3M7N9P',
                'pax' => 1,
                'requested_at' => '2026-11-10 08:45',
                'note' => 'Short regional flight',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Emirates',
                        'flight_no' => 'EK848',
                        'from_code' => 'DXB',
                        'from_city' => 'Dubai',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-04',
                        'dep' => '10:30',
                        'arr' => '11:30',
                        'cls' => 'First',
                        'dur' => '1h 00m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Emirates',
                        'flight_no' => 'EK849',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'DXB',
                        'to_city' => 'Dubai',
                        'date' => '2026-12-06',
                        'dep' => '14:00',
                        'arr' => '16:55',
                        'cls' => 'First',
                        'dur' => '0h 55m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // Olaf Scholz - Germany
            [
                'guest_name' => 'Olaf Scholz',
                'code' => 'FL-005',
                'status' => 'confirmed',
                'ref' => 'LH9K4P2M',
                'pax' => 1,
                'requested_at' => '2026-10-25 16:30',
                'note' => 'German Chancellor',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Lufthansa',
                        'flight_no' => 'LH632',
                        'from_code' => 'FRA',
                        'from_city' => 'Frankfurt',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-03',
                        'dep' => '22:00',
                        'arr' => '06:15',
                        'cls' => 'Business',
                        'dur' => '5h 15m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Lufthansa',
                        'flight_no' => 'LH633',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'FRA',
                        'to_city' => 'Frankfurt',
                        'date' => '2026-12-09',
                        'dep' => '03:20',
                        'arr' => '08:05',
                        'cls' => 'Business',
                        'dur' => '6h 45m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // Arsène Wenger - France
            [
                'guest_name' => 'Arsène Wenger',
                'code' => 'FL-006',
                'status' => 'new',
                'ref' => null,
                'pax' => 1,
                'requested_at' => '2026-11-20 10:00',
                'note' => 'Awaiting confirmation',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR38',
                        'from_code' => 'CDG',
                        'from_city' => 'Paris',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-02',
                        'dep' => '14:20',
                        'arr' => '22:35',
                        'cls' => 'Business',
                        'dur' => '6h 15m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR39',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'CDG',
                        'to_city' => 'Paris',
                        'date' => '2026-12-12',
                        'dep' => '08:15',
                        'arr' => '13:45',
                        'cls' => 'Business',
                        'dur' => '7h 30m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // Kylian Mbappé - France
            [
                'guest_name' => 'Kylian Mbappé',
                'code' => 'FL-007',
                'status' => 'confirmed',
                'ref' => 'QR6L8M3N',
                'pax' => 1,
                'requested_at' => '2026-11-05 13:45',
                'note' => '',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR38',
                        'from_code' => 'CDG',
                        'from_city' => 'Paris',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-01',
                        'dep' => '14:20',
                        'arr' => '22:35',
                        'cls' => 'Business',
                        'dur' => '6h 15m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR39',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'CDG',
                        'to_city' => 'Paris',
                        'date' => '2026-12-14',
                        'dep' => '08:15',
                        'arr' => '13:45',
                        'cls' => 'Business',
                        'dur' => '7h 30m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // Cristiano Ronaldo - Portugal
            [
                'guest_name' => 'Cristiano Ronaldo',
                'code' => 'FL-008',
                'status' => 'confirmed',
                'ref' => 'QR7N2P5K',
                'pax' => 1,
                'requested_at' => '2026-10-28 15:20',
                'note' => 'VIP athlete',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR356',
                        'from_code' => 'LIS',
                        'from_city' => 'Lisbon',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-01',
                        'dep' => '01:40',
                        'arr' => '10:40',
                        'cls' => 'Business',
                        'dur' => '7h 00m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR357',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'LIS',
                        'to_city' => 'Lisbon',
                        'date' => '2026-12-13',
                        'dep' => '02:35',
                        'arr' => '07:45',
                        'cls' => 'Business',
                        'dur' => '8h 10m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // Lewis Hamilton - UK
            [
                'guest_name' => 'Lewis Hamilton',
                'code' => 'FL-009',
                'status' => 'change',
                'ref' => 'QR4M9L2P',
                'pax' => 1,
                'requested_at' => '2026-11-08 09:15',
                'note' => 'Date change requested - F1 schedule conflict',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR2',
                        'from_code' => 'LHR',
                        'from_city' => 'London',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-04',
                        'dep' => '20:35',
                        'arr' => '05:45',
                        'cls' => 'Business',
                        'dur' => '6h 10m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR3',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'LHR',
                        'to_city' => 'London',
                        'date' => '2026-12-11',
                        'dep' => '07:55',
                        'arr' => '12:35',
                        'cls' => 'Business',
                        'dur' => '7h 40m',
                        'sort' => 1,
                    ],
                ],
            ],
            
            // António Guterres - Portugal
            [
                'guest_name' => 'António Guterres',
                'code' => 'FL-010',
                'status' => 'new',
                'ref' => null,
                'pax' => 1,
                'requested_at' => '2026-11-18 14:30',
                'note' => 'UN Secretary-General - pending RSVP confirmation',
                'legs' => [
                    [
                        'dir' => 'Inbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR704',
                        'from_code' => 'JFK',
                        'from_city' => 'New York',
                        'to_code' => 'DOH',
                        'to_city' => 'Doha',
                        'date' => '2026-12-03',
                        'dep' => '21:35',
                        'arr' => '18:10',
                        'cls' => 'First',
                        'dur' => '12h 35m',
                        'sort' => 0,
                    ],
                    [
                        'dir' => 'Outbound',
                        'airline' => 'Qatar Airways',
                        'flight_no' => 'QR701',
                        'from_code' => 'DOH',
                        'from_city' => 'Doha',
                        'to_code' => 'JFK',
                        'to_city' => 'New York',
                        'date' => '2026-12-10',
                        'dep' => '08:45',
                        'arr' => '15:15',
                        'cls' => 'First',
                        'dur' => '14h 30m',
                        'sort' => 1,
                    ],
                ],
            ],
        ];

        foreach ($flightRequests as $requestData) {
            // Find the guest by name
            $guest = $guests->firstWhere('name', $requestData['guest_name']);
            
            if (!$guest) {
                $this->command->warn("Guest '{$requestData['guest_name']}' not found. Skipping.");
                continue;
            }

            // Create flight request
            $flightRequestId = DB::table('flight_requests')->insertGetId([
                'guest_id' => $guest->id,
                'code' => $requestData['code'],
                'status' => $requestData['status'],
                'ref' => $requestData['ref'],
                'pax' => $requestData['pax'],
                'requested_at' => $requestData['requested_at'],
                'note' => $requestData['note'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create flight legs
            foreach ($requestData['legs'] as $legData) {
                DB::table('flight_legs')->insert([
                    'flight_request_id' => $flightRequestId,
                    'dir' => $legData['dir'],
                    'airline' => $legData['airline'],
                    'flight_no' => $legData['flight_no'],
                    'from_code' => $legData['from_code'],
                    'from_city' => $legData['from_city'],
                    'to_code' => $legData['to_code'],
                    'to_city' => $legData['to_city'],
                    'date' => $legData['date'],
                    'dep' => $legData['dep'],
                    'arr' => $legData['arr'],
                    'cls' => $legData['cls'],
                    'dur' => $legData['dur'],
                    'sort' => $legData['sort'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info("Created flight request {$requestData['code']} for {$requestData['guest_name']}");
        }

        $this->command->info('Flight seeding completed successfully!');
    }
}
