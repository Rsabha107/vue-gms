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
        // First, update existing status values to match accommodation_statuses ids
        DB::table('accommodation_requests')
            ->where('status', 'pending')
            ->update(['status' => 'new']);

        // Rename status column to status_id
        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->renameColumn('status', 'status_id');
        });

        // Add foreign key constraint
        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->foreign('status_id')
                ->references('id')
                ->on('accommodation_statuses')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
        });

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->renameColumn('status_id', 'status');
        });
    }
};
