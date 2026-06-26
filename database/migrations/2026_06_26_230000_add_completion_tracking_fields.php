<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Accommodation: check-in/check-out confirmation
        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('notes');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
        });

        // Flights: boarding confirmation
        Schema::table('flight_requests', function (Blueprint $table) {
            $table->timestamp('boarded_at')->nullable()->after('note');
        });

        // Transport: service receipt confirmation
        Schema::table('transport_requests', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_out_at']);
        });
        Schema::table('flight_requests', function (Blueprint $table) {
            $table->dropColumn('boarded_at');
        });
        Schema::table('transport_requests', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
