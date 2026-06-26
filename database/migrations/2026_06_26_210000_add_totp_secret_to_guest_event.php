<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('guest_event', function (Blueprint $table) {
            $table->string('totp_secret')->nullable()->after('passport_front');
        });
    }

    public function down(): void
    {
        Schema::table('guest_event', function (Blueprint $table) {
            $table->dropColumn('totp_secret');
        });
    }
};
