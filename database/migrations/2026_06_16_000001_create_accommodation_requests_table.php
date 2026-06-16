<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accommodation module (FR 4.5). Hotel booking requests for guests.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('accommodation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();             // ACC-001
            $table->string('status_id')->default('new');  // FK to accommodation_statuses
            $table->foreign('status_id')->references('id')->on('accommodation_statuses')->onDelete('restrict');
            $table->string('hotel_code')->nullable();     // HOT-01
            $table->string('hotel_name');                 // Four Seasons Doha
            $table->string('room_type');                  // Presidential Suite, Executive Suite, etc.
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');       // Calculated nights
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_requests');
    }
};
