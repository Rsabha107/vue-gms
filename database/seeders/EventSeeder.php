<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get venues
        $lusailStadium = Venue::where('name', 'Lusail Stadium')->first();
        $alBaytStadium = Venue::where('name', 'Al Bayt Stadium')->first();
        $educationCity = Venue::where('name', 'Education City')->first();

        // Create events and attach venues
        $dohaCup = Event::create([
            'name' => "Doha Cup '26",
            'subtitle' => 'International Football Tournament',
            'location' => 'Lusail, Qatar',
            'date_start' => '2026-01-15',
            'date_end' => '2026-02-28',
            'logo' => '🏆',
            'active_flag' => true,
        ]);
        $dohaCup->venues()->attach([$lusailStadium->id, $alBaytStadium->id]);

        $arabianGulf = Event::create([
            'name' => "Arabian Gulf Championship",
            'subtitle' => 'Regional Football Competition',
            'location' => 'Doha, Qatar',
            'date_start' => '2026-03-10',
            'date_end' => '2026-03-25',
            'logo' => '⚽',
            'active_flag' => true,
        ]);
        $arabianGulf->venues()->attach([$alBaytStadium->id, $educationCity->id]);

        $diplomaticSummit = Event::create([
            'name' => "Diplomatic Summit 2026",
            'subtitle' => 'International Relations Conference',
            'location' => 'Doha, Qatar',
            'date_start' => '2026-05-05',
            'date_end' => '2026-05-08',
            'logo' => '🏛️',
            'active_flag' => true,
        ]);
        $diplomaticSummit->venues()->attach([$educationCity->id]);
    }
}
