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
        // Drop foreign key constraint on invitations table first
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['guest_id']);
        });

        // Backup data from guests table
        $guests = DB::table('guests')->get();
        
        // Truncate invitations (we'll need to reseed)
        DB::table('invitations')->truncate();
        
        // Truncate guests table
        DB::table('guests')->truncate();

        // Change guests.id to bigint auto-increment
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->id()->first(); // This creates a BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        });

        // Change invitations.guest_id to bigint
        Schema::table('invitations', function (Blueprint $table) {
            $table->unsignedBigInteger('guest_id')->change();
        });

        // Re-add the foreign key constraint
        Schema::table('invitations', function (Blueprint $table) {
            $table->foreign('guest_id')
                ->references('id')
                ->on('guests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['guest_id']);
        });

        // Change guests.id back to varchar
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->string('id', 10)->primary()->first();
        });

        // Change invitations.guest_id back to varchar
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('guest_id', 10)->change();
        });

        // Re-add the foreign key constraint
        Schema::table('invitations', function (Blueprint $table) {
            $table->foreign('guest_id')
                ->references('id')
                ->on('guests')
                ->onDelete('cascade');
        });
    }
};
