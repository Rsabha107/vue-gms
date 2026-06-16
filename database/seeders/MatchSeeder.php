<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Venue;
use App\Models\GameMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MatchSeeder extends Seeder
{
    public function run(): void
    {
        // Get the active event (Doha Cup '26)
        $event = Event::where('active_flag', true)->first();
        if (!$event) {
            $this->command->warn('No active event found. Skipping match seeding.');
            return;
        }

        // Get venues by name
        $lusail = Venue::where('name', 'Lusail Stadium')->first();
        $alBayt = Venue::where('name', 'Al Bayt Stadium')->first();
        $educationCity = Venue::where('name', 'Education City Stadium')->first();

        if (!$lusail || !$alBayt || !$educationCity) {
            $this->command->warn('Required venues not found. Run VenueSeeder first.');
            return;
        }

        $matches = [
            [
                'event_id' => $event->id,
                'venue_id' => $lusail->id,
                'stage' => 'Opening',
                'label' => 'Opening Ceremony & Group A',
                'team_a_name' => 'Qatar',
                'team_a_flag' => 'QA',
                'team_b_name' => 'Japan',
                'team_b_flag' => 'JP',
                'date' => '2026-08-10',
                'day' => 'Mon',
                'time' => '19:00',
                'capacity' => 320,
                'sold' => 248,
                'featured' => true,
                'tbd' => false,
            ],
            [
                'event_id' => $event->id,
                'venue_id' => $alBayt->id,
                'stage' => 'Group B',
                'label' => 'Group Stage',
                'team_a_name' => 'Brazil',
                'team_a_flag' => 'BR',
                'team_b_name' => 'Italy',
                'team_b_flag' => 'IT',
                'date' => '2026-08-12',
                'day' => 'Wed',
                'time' => '18:00',
                'capacity' => 300,
                'sold' => 140,
                'featured' => false,
                'tbd' => false,
            ],
            [
                'event_id' => $event->id,
                'venue_id' => $educationCity->id,
                'stage' => 'Group C',
                'label' => 'Group Stage',
                'team_a_name' => 'Sweden',
                'team_a_flag' => 'SE',
                'team_b_name' => 'Nigeria',
                'team_b_flag' => 'NG',
                'date' => '2026-08-13',
                'day' => 'Thu',
                'time' => '21:00',
                'capacity' => 300,
                'sold' => 96,
                'featured' => false,
                'tbd' => false,
            ],
            [
                'event_id' => $event->id,
                'venue_id' => $lusail->id,
                'stage' => 'Quarter Final',
                'label' => 'Quarter Final 1',
                'team_a_name' => 'Winner Gp A',
                'team_a_flag' => null,
                'team_b_name' => 'Runner-up Gp B',
                'team_b_flag' => null,
                'date' => '2026-08-15',
                'day' => 'Sat',
                'time' => '18:00',
                'capacity' => 320,
                'sold' => 212,
                'featured' => false,
                'tbd' => true,
            ],
            [
                'event_id' => $event->id,
                'venue_id' => $alBayt->id,
                'stage' => 'Quarter Final',
                'label' => 'Quarter Final 2',
                'team_a_name' => 'Winner Gp C',
                'team_a_flag' => null,
                'team_b_name' => 'Runner-up Gp D',
                'team_b_flag' => null,
                'date' => '2026-08-16',
                'day' => 'Sun',
                'time' => '21:00',
                'capacity' => 300,
                'sold' => 180,
                'featured' => false,
                'tbd' => true,
            ],
            [
                'event_id' => $event->id,
                'venue_id' => $lusail->id,
                'stage' => 'Semi Final',
                'label' => 'Semi Final I',
                'team_a_name' => 'TBD',
                'team_a_flag' => null,
                'team_b_name' => 'TBD',
                'team_b_flag' => null,
                'date' => '2026-08-18',
                'day' => 'Tue',
                'time' => '18:00',
                'capacity' => 320,
                'sold' => 266,
                'featured' => false,
                'tbd' => true,
            ],
            [
                'event_id' => $event->id,
                'venue_id' => $lusail->id,
                'stage' => 'Semi Final',
                'label' => 'Semi Final II',
                'team_a_name' => 'TBD',
                'team_a_flag' => null,
                'team_b_name' => 'TBD',
                'team_b_flag' => null,
                'date' => '2026-08-19',
                'day' => 'Wed',
                'time' => '21:00',
                'capacity' => 320,
                'sold' => 240,
                'featured' => false,
                'tbd' => true,
            ],
            [
                'event_id' => $event->id,
                'venue_id' => $lusail->id,
                'stage' => 'Final',
                'label' => 'The Final',
                'team_a_name' => 'TBD',
                'team_a_flag' => null,
                'team_b_name' => 'TBD',
                'team_b_flag' => null,
                'date' => '2026-08-22',
                'day' => 'Sat',
                'time' => '20:00',
                'capacity' => 320,
                'sold' => 300,
                'featured' => true,
                'tbd' => true,
            ],
        ];

        $now = now();
        $created = 0;

        foreach ($matches as $data) {
            // Check if match already exists (by event, venue, date, time)
            $existing = GameMatch::where('event_id', $data['event_id'])
                ->where('venue_id', $data['venue_id'])
                ->where('date', $data['date'])
                ->where('time', $data['time'])
                ->first();

            if ($existing) {
                // Update existing match
                $existing->update($data);
                $this->command->info("✓ Updated match: {$data['label']} on {$data['date']}");
            } else {
                // Create new match
                GameMatch::create(array_merge($data, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $created++;
                $this->command->info("✓ Created match: {$data['label']} on {$data['date']}");
            }
        }

        if ($created > 0) {
            $this->command->info("\n✓ Created {$created} new matches for {$event->name}");
        }
    }
}
