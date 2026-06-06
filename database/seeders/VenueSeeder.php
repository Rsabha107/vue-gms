<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            ['name' => 'Lusail Stadium',        'city' => 'Lusail',    'country' => 'QA', 'capacity' => 88966, 'type' => 'stadium', 'notes' => 'Tournament showpiece — hosts the Final.'],
            ['name' => 'Al Bayt Stadium',        'city' => 'Al Khor',   'country' => 'QA', 'capacity' => 68895, 'type' => 'stadium', 'notes' => 'Tented architecture, north of Doha.'],
            ['name' => 'Education City Stadium', 'city' => 'Al Rayyan', 'country' => 'QA', 'capacity' => 44667, 'type' => 'stadium', 'notes' => '"Diamond in the Desert".'],
            ['name' => 'Stadium 974',            'city' => 'Doha',      'country' => 'QA', 'capacity' => 44089, 'type' => 'stadium', 'notes' => 'Modular shipping-container build.'],
            ['name' => 'Khalifa International',  'city' => 'Doha',      'country' => 'QA', 'capacity' => 45857, 'type' => 'stadium', 'notes' => 'Historic national stadium.'],
        ];

        $now = now();

        foreach ($venues as $data) {
            $existing = DB::table('venues')->where('name', $data['name'])->first();
            if ($existing) {
                DB::table('venues')->where('id', $existing->id)->update(array_merge($data, ['updated_at' => $now]));
            } else {
                DB::table('venues')->insert(array_merge($data, [
                    'title'       => $data['name'],
                    'short_name'  => $data['name'],
                    'active_flag' => 1,
                    'created_by'  => 1,
                    'updated_by'  => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]));
            }
        }
    }
}
