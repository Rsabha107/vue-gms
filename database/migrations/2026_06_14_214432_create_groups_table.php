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
        Schema::create('groups', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('name', 50);
            $table->string('label', 150);
            $table->timestamps();
        });

        // Seed default groups from prototype
        DB::table('groups')->insert([
            [
                'id' => 'GRP-LOC',
                'name' => 'LOC',
                'label' => 'Local Organising Committee',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'GRP-MOI',
                'name' => 'MOI',
                'label' => 'Ministry of Interior',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'GRP-FIFA',
                'name' => 'FIFA',
                'label' => 'FIFA Delegation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'GRP-VVIP',
                'name' => 'State Guests',
                'label' => 'Head of State / Royal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'GRP-CORP',
                'name' => 'Corporate',
                'label' => 'Corporate Partners',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'GRP-MEDIA',
                'name' => 'Media',
                'label' => 'Accredited Media',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'GRP-SPORT',
                'name' => 'Sports Figures',
                'label' => 'Athletes & Officials',
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
        Schema::dropIfExists('groups');
    }
};
