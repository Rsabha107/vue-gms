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
        Schema::table('invitations', function (Blueprint $table) {
            // Drop the existing status enum column
            $table->dropColumn('status');
        });

        Schema::table('invitations', function (Blueprint $table) {
            // Add status_id foreign key
            $table->unsignedBigInteger('status_id')->after('event_id')->nullable();
            $table->foreign('status_id')
                ->references('id')
                ->on('invitation_statuses')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->enum('status', ['draft', 'sent', 'confirmed', 'declined'])->default('draft');
        });
    }
};
