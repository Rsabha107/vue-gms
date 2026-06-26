<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('guest_event', function (Blueprint $table) {
            $table->string('passport_no')->nullable()->after('companions');
            $table->string('personal_photo')->nullable()->after('passport_no');
            $table->string('passport_front')->nullable()->after('personal_photo');
        });
    }

    public function down(): void
    {
        Schema::table('guest_event', function (Blueprint $table) {
            $table->dropColumn(['passport_no', 'personal_photo', 'passport_front']);
        });
    }
};
