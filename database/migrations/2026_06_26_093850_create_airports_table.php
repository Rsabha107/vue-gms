<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();

            $table->string('ident', 20)->unique();
            $table->string('type', 50)->nullable();

            $table->string('name');

            $table->string('latitude_deg', 30)->nullable();
            $table->string('longitude_deg', 30)->nullable();

            $table->integer('elevation_ft')->nullable();

            $table->string('continent', 10)->nullable();
            $table->string('iso_country', 5)->index();
            $table->string('iso_region', 20)->nullable();

            $table->string('municipality')->nullable();

            $table->boolean('scheduled_service')->default(false);

            $table->string('gps_code', 10)->nullable()->index();
            $table->string('iata_code', 10)->nullable()->index();
            $table->string('local_code', 10)->nullable();

            $table->string('home_link')->nullable();
            $table->string('wikipedia_link')->nullable();

            $table->text('keywords')->nullable();

            $table->timestamps();

            $table->index(['iso_country', 'municipality']);
            $table->index(['iata_code', 'gps_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};