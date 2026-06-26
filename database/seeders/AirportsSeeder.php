<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportsSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('data/airports.csv');

        if (! file_exists($file)) {
            $this->command->error('airports.csv not found.');
            return;
        }

        Airport::truncate();

        $handle = fopen($file, 'r');

        $header = fgetcsv($handle);

        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {

            $data = array_combine($header, $row);

            $batch[] = [

                'ident' => $data['ident'],

                'type' => $data['type'],

                'name' => $data['name'],

                'latitude_deg' => $data['latitude_deg'],

                'longitude_deg' => $data['longitude_deg'],

                'elevation_ft' => $data['elevation_ft'] ?: null,

                'continent' => $data['continent'],

                'iso_country' => $data['iso_country'],

                'iso_region' => $data['iso_region'],

                'municipality' => $data['municipality'],

                'scheduled_service' => $data['scheduled_service'] === 'yes',

                'gps_code' => $data['gps_code'],

                'iata_code' => $data['iata_code'],

                'local_code' => $data['local_code'],

                'home_link' => $data['home_link'],

                'wikipedia_link' => $data['wikipedia_link'],

                'keywords' => $data['keywords'],

                'created_at' => now(),

                'updated_at' => now(),
            ];

            if (count($batch) == 1000) {
                Airport::insert($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            Airport::insert($batch);
        }

        fclose($handle);

        $this->command->info('Airports imported successfully.');
    }
}