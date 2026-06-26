<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $statusMap = DB::table('invitation_statuses')->pluck('id', 'name')->toArray();

        // Drop the old FK to accommodation_statuses
        Schema::table('accommodation_requests', function (Blueprint $table) {
            // Drop foreign key if it exists
            try { $table->dropForeign(['status_id']); } catch (\Exception $e) {}
        });

        // Add temp column, migrate data, swap
        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('new_status_id')->nullable()->after('code');
        });

        $rows = DB::table('accommodation_requests')->get();
        foreach ($rows as $row) {
            $id = $statusMap[$row->status_id] ?? $statusMap['new'] ?? null;
            if ($id) {
                DB::table('accommodation_requests')->where('id', $row->id)->update(['new_status_id' => $id]);
            }
        }

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id')->nullable()->after('code');
        });

        DB::table('accommodation_requests')->update([
            'status_id' => DB::raw('new_status_id'),
        ]);

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->dropColumn('new_status_id');
            $table->foreign('status_id')->references('id')->on('invitation_statuses')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        $statusMap = DB::table('invitation_statuses')->pluck('name', 'id')->toArray();

        Schema::table('accommodation_requests', function (Blueprint $table) {
            try { $table->dropForeign(['status_id']); } catch (\Exception $e) {}
        });

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->string('old_status_id')->nullable()->after('code');
        });

        $rows = DB::table('accommodation_requests')->get();
        foreach ($rows as $row) {
            DB::table('accommodation_requests')->where('id', $row->id)->update([
                'old_status_id' => $statusMap[$row->status_id] ?? 'new',
            ]);
        }

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->string('status_id')->default('new')->after('code');
        });

        DB::table('accommodation_requests')->update([
            'status_id' => DB::raw('old_status_id'),
        ]);

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->dropColumn('old_status_id');
            $table->foreign('status_id')->references('id')->on('accommodation_statuses')->onDelete('restrict');
        });
    }
};
