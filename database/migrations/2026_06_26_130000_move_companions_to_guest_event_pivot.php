<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add companions column to guest_event pivot table
        Schema::table('guest_event', function (Blueprint $table) {
            $table->json('companions')->nullable()->after('invited_at');
        });

        // Migrate existing companion data from guests to guest_event
        $guests = DB::table('guests')
            ->whereNotNull('companionList')
            ->where('companionList', '!=', '[]')
            ->where('companionList', '!=', 'null')
            ->get();

        foreach ($guests as $guest) {
            // Get all events this guest is on
            $guestEvents = DB::table('guest_event')
                ->where('guest_id', $guest->id)
                ->get();

            // Copy companion list to each event attendance
            foreach ($guestEvents as $guestEvent) {
                DB::table('guest_event')
                    ->where('guest_id', $guest->id)
                    ->where('event_id', $guestEvent->event_id)
                    ->update(['companions' => $guest->companionList]);
            }
        }

        // Drop companions and companionList columns from guests table
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['companionList', 'companions']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add columns to guests table
        Schema::table('guests', function (Blueprint $table) {
            $table->json('companionList')->nullable();
            $table->integer('companions')->default(0);
        });

        // Migrate data back from guest_event to guests
        // Take companions from the first event they're on (best effort)
        $guestEvents = DB::table('guest_event')
            ->whereNotNull('companions')
            ->where('companions', '!=', '[]')
            ->where('companions', '!=', 'null')
            ->get()
            ->groupBy('guest_id');

        foreach ($guestEvents as $guestId => $events) {
            $firstEvent = $events->first();
            $companions = json_decode($firstEvent->companions, true);
            
            DB::table('guests')
                ->where('id', $guestId)
                ->update([
                    'companionList' => $firstEvent->companions,
                    'companions' => is_array($companions) ? count($companions) : 0
                ]);
        }

        // Drop companions column from guest_event
        Schema::table('guest_event', function (Blueprint $table) {
            $table->dropColumn('companions');
        });
    }
};
