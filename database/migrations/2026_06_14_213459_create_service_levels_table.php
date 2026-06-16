<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_levels', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('name', 100);
            $table->string('color', 7);
            $table->string('bg', 7);
            $table->integer('rank')->unsigned();
            $table->json('facilities')->nullable();
            $table->timestamps();
        });

        // Seed default service levels with React prototype colors
        DB::table('service_levels')->insert([
            [
                'id' => 'T1',
                'name' => 'Platinum',
                'color' => '#5b4a8a',
                'bg' => '#ece9f3',
                'rank' => 1,
                'facilities' => json_encode(['VIP Royal Lounge', 'Chauffeur Escort', 'Presidential Suite', 'Fine Dining', 'Dedicated Host', 'Airport Fast-Track']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'T2',
                'name' => 'Platinum',
                'color' => '#5b4a8a',
                'bg' => '#ece9f3',
                'rank' => 2,
                'facilities' => json_encode(['Executive Lounge', 'Private Driver', 'Luxury Suite', 'Premium Dining', 'Personal Host']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'T3',
                'name' => 'Gold',
                'color' => '#9a7430',
                'bg' => '#f3ecdd',
                'rank' => 3,
                'facilities' => json_encode(['Business Lounge', 'Shuttle Service', 'Premium Room', 'Restaurant Access']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'T4',
                'name' => 'Silver',
                'color' => '#7a756c',
                'bg' => '#eceae6',
                'rank' => 4,
                'facilities' => json_encode(['Lounge Access', 'Group Shuttle']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'T5',
                'name' => 'Silver',
                'color' => '#7a756c',
                'bg' => '#eceae6',
                'rank' => 5,
                'facilities' => json_encode(['Press Box', 'Media Kit', 'Photo Access']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_levels');
    }
};
