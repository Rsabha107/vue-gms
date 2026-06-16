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
        // Add event_id to guests table
        if (!Schema::hasColumn('guests', 'event_id')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->onDelete('cascade');
                $table->index('event_id');
            });
        }

        // Add event_id to flight_requests table
        if (!Schema::hasColumn('flight_requests', 'event_id')) {
            Schema::table('flight_requests', function (Blueprint $table) {
                $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->onDelete('cascade');
                $table->index('event_id');
            });
        }

        // invitations already has event_id, skipping
        
        // game_matches already has event_id, skipping
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('guests', 'event_id')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropColumn('event_id');
            });
        }

        if (Schema::hasColumn('flight_requests', 'event_id')) {
            Schema::table('flight_requests', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropColumn('event_id');
            });
        }
    }
};
