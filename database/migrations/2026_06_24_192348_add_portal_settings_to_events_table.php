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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('portal_enabled')->default(false)->after('active_flag');
            $table->enum('portal_auth_mode', ['signed_url', 'magic_link', 'full_auth'])->default('signed_url')->after('portal_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['portal_enabled', 'portal_auth_mode']);
        });
    }
};
