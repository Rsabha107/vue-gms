<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $statusMap = DB::table('invitation_statuses')->pluck('id', 'name')->toArray();

        Schema::table('transport_requests', function (Blueprint $table) {
            try { $table->dropForeign(['status_id']); } catch (\Exception $e) {}
        });

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('new_status_id')->nullable()->after('code');
        });

        $rows = DB::table('transport_requests')->get();
        foreach ($rows as $row) {
            $id = $statusMap[$row->status_id] ?? $statusMap['pending'] ?? null;
            if ($id) {
                DB::table('transport_requests')->where('id', $row->id)->update(['new_status_id' => $id]);
            }
        }

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id')->nullable()->after('code');
        });

        DB::table('transport_requests')->update(['status_id' => DB::raw('new_status_id')]);

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->dropColumn('new_status_id');
            $table->foreign('status_id')->references('id')->on('invitation_statuses')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        $statusMap = DB::table('invitation_statuses')->pluck('name', 'id')->toArray();

        Schema::table('transport_requests', function (Blueprint $table) {
            try { $table->dropForeign(['status_id']); } catch (\Exception $e) {}
        });

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->string('old_status_id')->nullable()->after('code');
        });

        $rows = DB::table('transport_requests')->get();
        foreach ($rows as $row) {
            DB::table('transport_requests')->where('id', $row->id)->update([
                'old_status_id' => $statusMap[$row->status_id] ?? 'pending',
            ]);
        }

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->string('status_id')->default('pending')->after('code');
        });

        DB::table('transport_requests')->update(['status_id' => DB::raw('old_status_id')]);

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->dropColumn('old_status_id');
            $table->foreign('status_id')->references('id')->on('transport_statuses')->onDelete('restrict');
        });
    }
};
