<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed venues first, then events
        $this->call([
            VenueSeeder::class,
            EventSeeder::class,
            InvitationStatusSeeder::class,
            GuestSeeder::class,
            MatchSeeder::class,
            HotelSeeder::class,
            AccommodationSeeder::class,
            RoomBlockSeeder::class,
            VehicleBlockSeeder::class,
            SeatingSeeder::class,
        ]);
    }
}
