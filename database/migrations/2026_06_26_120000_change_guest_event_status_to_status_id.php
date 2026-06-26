<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new status_id column
        Schema::table('guest_event', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id')->nullable()->after('event_id');
        });

        // Migrate data: map old string statuses to invitation_statuses IDs
        $statusMap = DB::table('invitation_statuses')
            ->select('id', 'name')
            ->get()
            ->keyBy('name')
            ->map(fn($status) => $status->id)
            ->toArray();

        // Update existing records
        foreach (['not_invited', 'invited', 'pending', 'accepted', 'declined', 'confirmed'] as $statusName) {
            if (isset($statusMap[$statusName])) {
                DB::table('guest_event')
                    ->where('status', $statusName)
                    ->update(['status_id' => $statusMap[$statusName]]);
            }
        }

        // Make status_id NOT NULL and add foreign key
        Schema::table('guest_event', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id')->nullable(false)->change();
            $table->foreign('status_id')->references('id')->on('invitation_statuses')->onDelete('restrict');
        });

        // Drop old status column and its index
        Schema::table('guest_event', function (Blueprint $table) {
            $table->dropIndex(['status']); // Drop index first
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        // Add back the old status column
        Schema::table('guest_event', function (Blueprint $table) {
            $table->string('status', 30)->default('not_invited')->after('event_id');
            $table->index('status');
        });

        // Migrate data back: map status_id to status names
        $statuses = DB::table('invitation_statuses')
            ->select('id', 'name')
            ->get();

        foreach ($statuses as $status) {
            DB::table('guest_event')
                ->where('status_id', $status->id)
                ->update(['status' => $status->name]);
        }

        // Drop the new status_id column
        Schema::table('guest_event', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
