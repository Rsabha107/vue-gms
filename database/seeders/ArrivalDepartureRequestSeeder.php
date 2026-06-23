<?php

namespace Database\Seeders;

use App\Models\ArrivalDepartureRequest;
use App\Models\Guest;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ArrivalDepartureRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requests = [
            ['refNo' => 'G-0002', 'status' => 'confirmed', 'type' => 'arrival',   'flight_no' => 'QR 2025', 'terminal' => 'VIP Terminal', 'datetime' => '2026-01-14 18:45', 'lounge' => 'Al Safwa Lounge', 'greeter' => 'Khalid Al-Hamadi',   'notes' => 'Diplomatic courtesy lane'],
            ['refNo' => 'G-0002', 'status' => 'pending',   'type' => 'departure', 'flight_no' => 'QR 2026', 'terminal' => 'VIP Terminal', 'datetime' => '2026-01-20 23:00', 'lounge' => 'Al Safwa Lounge', 'greeter' => 'Nour Al-Rashidi',    'notes' => ''],
            ['refNo' => 'G-0004', 'status' => 'confirmed', 'type' => 'arrival',   'flight_no' => 'QR 5',    'terminal' => 'Royal Terminal', 'datetime' => '2026-01-15 07:00', 'lounge' => 'Royal Lounge',    'greeter' => 'Sara Al-Jassim',     'notes' => 'Full state welcome ceremony'],
            ['refNo' => 'G-0003', 'status' => 'confirmed', 'type' => 'arrival',   'flight_no' => 'QR 47',   'terminal' => 'Terminal 1',     'datetime' => '2026-01-14 22:10', 'lounge' => 'Premium Lounge',  'greeter' => 'Mohammed Al-Dosari', 'notes' => ''],
            ['refNo' => 'G-0006', 'status' => 'confirmed', 'type' => 'arrival',   'flight_no' => 'LH 688',  'terminal' => 'Terminal 1',     'datetime' => '2026-01-15 19:30', 'lounge' => 'Premium Lounge',  'greeter' => 'Khalid Al-Hamadi',   'notes' => ''],
            ['refNo' => 'G-0005', 'status' => 'confirmed', 'type' => 'arrival',   'flight_no' => 'FZ 001',  'terminal' => 'VIP Terminal', 'datetime' => '2026-01-14 14:00', 'lounge' => 'Al Safwa Lounge', 'greeter' => 'Sara Al-Jassim',     'notes' => 'UAE State protocol'],
            ['refNo' => 'G-0009', 'status' => 'pending',   'type' => 'arrival',   'flight_no' => 'QR 271',  'terminal' => 'Terminal 2',     'datetime' => '2026-01-17 18:30', 'lounge' => 'Business Lounge', 'greeter' => 'Mohammed Al-Dosari', 'notes' => ''],
        ];

        foreach ($requests as $requestData) {
            $guest = Guest::where('reference_number', $requestData['refNo'])->first();
            
            if ($guest) {
                ArrivalDepartureRequest::create([
                    'guest_id' => $guest->id,
                    'status' => $requestData['status'],
                    'type' => $requestData['type'],
                    'flight_no' => $requestData['flight_no'],
                    'terminal' => $requestData['terminal'],
                    'datetime' => $requestData['datetime'],
                    'lounge' => $requestData['lounge'],
                    'greeter' => $requestData['greeter'],
                    'notes' => $requestData['notes'],
                ]);
            }
        }
    }
}
