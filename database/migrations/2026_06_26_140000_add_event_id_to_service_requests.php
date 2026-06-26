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
        // flight_requests, accommodation_requests, and transport_requests already have event_id
        // Only add to arrival_departure_requests which is missing it
        
        if (!Schema::hasColumn('arrival_departure_requests', 'event_id')) {
            Schema::table('arrival_departure_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('event_id')->nullable()->after('guest_id');
                $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
                $table->index(['guest_id', 'event_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('arrival_departure_requests', 'event_id')) {
            Schema::table('arrival_departure_requests', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropIndex(['guest_id', 'event_id']);
                $table->dropColumn('event_id');
            });
        }
    }
};
