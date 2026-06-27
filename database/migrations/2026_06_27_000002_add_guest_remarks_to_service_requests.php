<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('flight_requests', function (Blueprint $table) {
            $table->text('guest_remarks')->nullable()->after('note');
        });

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->text('guest_remarks')->nullable()->after('notes');
        });

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->text('guest_remarks')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('flight_requests', fn(Blueprint $t) => $t->dropColumn('guest_remarks'));
        Schema::table('accommodation_requests', fn(Blueprint $t) => $t->dropColumn('guest_remarks'));
        Schema::table('transport_requests', fn(Blueprint $t) => $t->dropColumn('guest_remarks'));
    }
};
