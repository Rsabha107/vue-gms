<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Service Levels / Invitations "guest services" overview reads a per-guest
 * facilities snapshot ({flight, hotel, seat, transport, ad} → status objects).
 * Stored as JSON on the guest so the overview is a single read.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
            $table->json('facilities')->nullable()->after('passport');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['phone', 'facilities']);
        });
    }
};
