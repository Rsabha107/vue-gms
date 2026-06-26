<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('area')->nullable();
            $table->unsignedTinyInteger('stars')->default(5);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Add hotel_id FK to room_blocks
        Schema::table('room_blocks', function (Blueprint $table) {
            $table->foreignId('hotel_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });

        // Add hotel_id FK to accommodation_requests
        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->foreignId('hotel_id')->nullable()->after('status_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hotel_id');
        });

        Schema::table('room_blocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hotel_id');
        });

        Schema::dropIfExists('hotels');
    }
};
