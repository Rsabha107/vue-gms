<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = [
            ['code' => 'HOT-01', 'name' => 'Four Seasons Doha',      'area' => 'West Bay',    'stars' => 5],
            ['code' => 'HOT-02', 'name' => 'Mandarin Oriental Doha',  'area' => 'Msheireb',    'stars' => 5],
            ['code' => 'HOT-03', 'name' => 'St. Regis Doha',          'area' => 'West Bay',    'stars' => 5],
            ['code' => 'HOT-04', 'name' => 'Banyan Tree Doha',        'area' => 'Lusail',      'stars' => 5],
            ['code' => 'HOT-05', 'name' => 'Marsa Malaz Kempinski',   'area' => 'Pearl Island', 'stars' => 5],
            ['code' => 'HOT-06', 'name' => 'InterContinental Doha',   'area' => 'West Bay',    'stars' => 5],
            ['code' => 'HOT-07', 'name' => 'Waldorf Astoria Lusail',  'area' => 'Lusail',      'stars' => 5],
        ];

        foreach ($hotels as $hotel) {
            Hotel::create($hotel);
        }

        $this->command->info('✓ ' . count($hotels) . ' hotels seeded.');
    }
}
