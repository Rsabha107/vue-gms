<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('guest_event', function (Blueprint $table) {
            $table->enum('attendance_mode', ['matches', 'services_only', 'both'])->default('matches')->after('totp_secret');
        });
    }

    public function down(): void
    {
        Schema::table('guest_event', function (Blueprint $table) {
            $table->dropColumn('attendance_mode');
        });
    }
};
