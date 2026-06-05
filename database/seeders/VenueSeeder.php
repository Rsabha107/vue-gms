<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venues = [
            [
                'name' => 'Lusail Stadium',
                'city' => 'Lusail',
                'country' => 'QA',
                'capacity' => 80000,
                'type' => 'stadium',
            ],
            [
                'name' => 'Al Bayt Stadium',
                'city' => 'Al Khor',
                'country' => 'QA',
                'capacity' => 60000,
                'type' => 'stadium',
            ],
            [
                'name' => 'Education City',
                'city' => 'Al Rayyan',
                'country' => 'QA',
                'capacity' => 45000,
                'type' => 'stadium',
            ],
        ];

        foreach ($venues as $venue) {
            Venue::create($venue);
        }
    }
}
