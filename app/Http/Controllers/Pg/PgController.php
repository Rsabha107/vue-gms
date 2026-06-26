<?php

namespace App\Http\Controllers\Pg;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Inertia\Inertia;

class PgController extends Controller
{
    public function index()
    {
        $event = GmsMockData::getEvent();

        // Mock guest data
        $guest = [
            'id' => 1,
            'name' => 'Ahmed Al-Rashed',
            'firstName' => 'Ahmed',
            'lastName' => 'Al-Rashed',
            'title' => 'Senior Correspondent',
            'email' => 'a.rashed@aljazeera.net',
            'phone' => '+974 4489 4511',
            'nationality' => 'QA',
            'tier' => 'T1',
            'tier_info' => ['name' => 'Platinum', 'color' => '#8a1f3d'],
            'group' => ['name' => 'Accredited Media', 'label' => 'Accredited Media'],
            'reference_number' => 'GQ17',
        ];

        // Mock timeline
        $timeline = [
            ['time' => '08:30', 'date' => 'Today', 'title' => 'Airport pickup', 'subtitle' => 'HIA Terminal 1 · Mercedes S-Class', 'icon' => 'car', 'done' => true, 'next' => false],
            ['time' => '10:00', 'date' => 'Today', 'title' => 'Hotel check-in', 'subtitle' => 'The Ned Doha · Deluxe King', 'icon' => 'building', 'done' => true, 'next' => false],
            ['time' => '14:00', 'date' => 'Today', 'title' => 'Stadium transfer', 'subtitle' => 'Lusail Stadium · Block A Row 2', 'icon' => 'car', 'done' => false, 'next' => true],
            ['time' => '18:00', 'date' => 'Today', 'title' => 'Brazil vs Italy', 'subtitle' => 'Group B · Lusail Stadium', 'icon' => 'ticket', 'done' => false, 'next' => false],
            ['time' => '22:00', 'date' => 'Today', 'title' => 'Return to hotel', 'subtitle' => 'Lusail Stadium → The Ned Doha', 'icon' => 'car', 'done' => false, 'next' => false],
        ];

        // Mock matches
        $matches = [
            ['id' => 1, 'homeTeam' => 'Brazil', 'awayTeam' => 'Italy', 'homeCode' => 'BR', 'awayCode' => 'IT', 'stage' => 'Group B', 'tier' => 'VIP', 'date' => '2026-08-12', 'time' => '18:00', 'venue' => 'Al Khor', 'venueFull' => 'Al Bayt Stadium', 'gate' => 'Gate 4 · VIP North', 'access' => 'VIP Tribune', 'seat' => ['block' => 'A', 'row' => '2', 'number' => '07 · 08'], 'upNext' => true],
            ['id' => 2, 'homeTeam' => 'Winner Gp A', 'awayTeam' => 'Runner-up Gp B', 'homeCode' => '', 'awayCode' => '', 'stage' => 'Quarter Final', 'tier' => 'VVIP', 'date' => '2026-08-15', 'time' => '18:00', 'venue' => 'Lusail', 'venueFull' => 'Lusail Stadium', 'gate' => 'Gate 1 · VVIP Lounge', 'access' => 'VVIP Tribune', 'seat' => ['block' => 'A', 'row' => '1', 'number' => '03 · 04'], 'upNext' => false],
            ['id' => 3, 'homeTeam' => 'TBD', 'awayTeam' => 'TBD', 'homeCode' => '', 'awayCode' => '', 'stage' => 'Final', 'tier' => 'VVIP', 'date' => '2026-08-22', 'time' => '18:00', 'venue' => 'Lusail', 'venueFull' => 'Lusail Stadium', 'gate' => 'Gate 1 · VVIP Lounge', 'access' => 'VVIP Tribune', 'seat' => null, 'upNext' => false],
        ];

        // Mock flights
        $flights = [
            ['id' => 1, 'code' => 'FL-001', 'airline' => 'Qatar Airways', 'flightNo' => 'QR 304', 'origin' => 'LHR', 'originCity' => 'London', 'destination' => 'DOH', 'destCity' => 'Doha', 'date' => '2026-08-09', 'time' => '08:30', 'duration' => '6h 45m', 'class' => 'Business', 'pax' => 1],
            ['id' => 2, 'code' => 'FL-002', 'airline' => 'Qatar Airways', 'flightNo' => 'QR 305', 'origin' => 'DOH', 'originCity' => 'Doha', 'destination' => 'LHR', 'destCity' => 'London', 'date' => '2026-08-23', 'time' => '14:00', 'duration' => '7h 15m', 'class' => 'Business', 'pax' => 1],
        ];

        // Mock transport movements
        $transports = [
            'driver' => ['name' => 'Hamad A.', 'phone' => '+974 5555 1234'],
            'vehicle' => 'Mercedes-Benz S-Class',
            'plate' => 'QA 1074',
            'status' => 'confirmed',
            'movements' => [
                ['id' => 1, 'type' => 'Airport pickup', 'date' => '2026-08-09', 'time' => '06:30', 'pickup' => 'Hamad Int\'l (HIA)', 'dropoff' => 'Mandarin Oriental', 'done' => true],
                ['id' => 2, 'type' => 'Match transfer', 'date' => '2026-08-12', 'time' => '16:00', 'pickup' => 'Mandarin Oriental', 'dropoff' => 'Al Bayt Stadium', 'done' => false],
                ['id' => 3, 'type' => 'Return to hotel', 'date' => '2026-08-12', 'time' => '21:30', 'pickup' => 'Al Bayt Stadium', 'dropoff' => 'Mandarin Oriental', 'done' => false],
                ['id' => 4, 'type' => 'Match transfer', 'date' => '2026-08-15', 'time' => '16:00', 'pickup' => 'Mandarin Oriental', 'dropoff' => 'Lusail Stadium', 'done' => false],
                ['id' => 5, 'type' => 'Return to hotel', 'date' => '2026-08-15', 'time' => '21:30', 'pickup' => 'Lusail Stadium', 'dropoff' => 'Mandarin Oriental', 'done' => false],
                ['id' => 6, 'type' => 'Airport departure', 'date' => '2026-08-23', 'time' => '10:00', 'pickup' => 'Mandarin Oriental', 'dropoff' => 'Hamad Int\'l (HIA)', 'done' => false],
            ],
        ];

        // Mock services summary
        $services = [
            'flightCount' => 2,
            'flightStatus' => 'confirmed',
            'hotelName' => 'The Ned Doha',
            'hotelDates' => '9 Aug — 23 Aug',
            'hotelStatus' => 'confirmed',
            'transportCount' => 6,
            'transportStatus' => 'confirmed',
            'matchCount' => 3,
            'nextMatch' => 'Qatar vs Japan · 10 Aug',
        ];

        // Events the guest is invited to
        $events = GmsMockData::getEvents();
        $eventsList = collect($events)->map(fn($e) => [
            'id'        => $e['id'],
            'name'      => $e['name'],
            'subtitle'  => $e['subtitle'] ?? '',
            'location'  => $e['location'] ?? '',
            'logo'      => $e['logo'] ?? '🏆',
            'dateStart' => $e['dateStart'] ?? $e['date_start'] ?? null,
            'dateEnd'   => $e['dateEnd'] ?? $e['date_end'] ?? null,
            'dates'     => $e['dates'] ?? '',
        ])->toArray();

        return Inertia::render('Pg/Index', [
            'guest'      => $guest,
            'event'      => $event,
            'events'     => $eventsList,
            'timeline'   => $timeline,
            'matches'    => $matches,
            'flights'    => $flights,
            'transports' => $transports,
            'services'   => $services,
        ]);
    }
}
