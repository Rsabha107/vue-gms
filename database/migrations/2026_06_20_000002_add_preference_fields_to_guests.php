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
        Schema::table('guests', function (Blueprint $table) {
            $table->text('flightPreferences')->nullable()->after('facilityOverrides');
            $table->text('accommodationPreferences')->nullable()->after('flightPreferences');
            $table->text('transportationPreferences')->nullable()->after('accommodationPreferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['flightPreferences', 'accommodationPreferences', 'transportationPreferences']);
        });
    }
};
