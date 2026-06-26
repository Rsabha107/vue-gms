<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add missing statuses to invitation_statuses
        $now = now();
        $existing = DB::table('invitation_statuses')->pluck('name')->toArray();

        $toAdd = [
            ['name' => 'new',       'label' => 'New',       'description' => 'Newly created request',   'color' => '#f59e0b', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'change',    'label' => 'Change',    'description' => 'Change request',          'color' => '#3b82f6', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'cancelled', 'label' => 'Cancelled', 'description' => 'Request cancelled',       'color' => '#ef4444', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($toAdd as $status) {
            if (!in_array($status['name'], $existing)) {
                DB::table('invitation_statuses')->insert($status);
            }
        }

        // Build name→id map
        $statusMap = DB::table('invitation_statuses')->pluck('id', 'name')->toArray();

        // Add status_id column
        Schema::table('flight_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id')->nullable()->after('code');
        });

        // Migrate existing string values to IDs
        $flights = DB::table('flight_requests')->whereNotNull('status')->get();
        foreach ($flights as $flight) {
            $id = $statusMap[$flight->status] ?? $statusMap['new'] ?? null;
            if ($id) {
                DB::table('flight_requests')->where('id', $flight->id)->update(['status_id' => $id]);
            }
        }

        // Drop old status column, add FK
        Schema::table('flight_requests', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->foreign('status_id')->references('id')->on('invitation_statuses')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        $statusMap = DB::table('invitation_statuses')->pluck('name', 'id')->toArray();

        Schema::table('flight_requests', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->string('status')->default('new')->after('code');
        });

        $flights = DB::table('flight_requests')->whereNotNull('status_id')->get();
        foreach ($flights as $flight) {
            DB::table('flight_requests')->where('id', $flight->id)->update([
                'status' => $statusMap[$flight->status_id] ?? 'new',
            ]);
        }

        Schema::table('flight_requests', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });
    }
};
