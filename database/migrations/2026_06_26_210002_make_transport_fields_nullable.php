<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transport_requests', function (Blueprint $table) {
            $table->string('type', 60)->nullable()->change();
            $table->string('pickup_location', 120)->nullable()->change();
            $table->string('dropoff_location', 120)->nullable()->change();
            $table->string('datetime')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transport_requests', function (Blueprint $table) {
            $table->string('type', 60)->nullable(false)->change();
            $table->string('pickup_location', 120)->nullable(false)->change();
            $table->string('dropoff_location', 120)->nullable(false)->change();
            $table->string('datetime')->nullable(false)->change();
        });
    }
};
