<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add preference_overrides to guest_event pivot table to allow per-event
     * overrides of global preferences (flight, accommodation, transportation).
     * Follows the same pattern as facilityOverrides on the guests table.
     */
    public function up(): void
    {
        Schema::table('guest_event', function (Blueprint $table) {
            $table->json('preference_overrides')->nullable()->after('companions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_event', function (Blueprint $table) {
            $table->dropColumn('preference_overrides');
        });
    }
};
