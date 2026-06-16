<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flights module (FR 4.4). One flight_request per international guest, each with
 * an inbound + outbound leg (or more). `flight_no` already includes the QR
 * prefix in the prototype — store it verbatim.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('flight_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();             // FL-001
            $table->string('status')->default('new');     // new · confirmed · change · cancelled
            $table->string('ref', 12)->nullable();        // PNR
            $table->unsignedSmallInteger('pax')->default(1);
            $table->string('requested_at')->nullable();   // display string
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('flight_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_request_id')->constrained()->cascadeOnDelete();
            $table->string('dir', 12);                    // Inbound · Outbound
            $table->string('airline')->default('Qatar Airways');
            $table->string('flight_no', 12);              // QR8
            $table->string('from_code', 8);
            $table->string('from_city')->nullable();
            $table->string('to_code', 8);
            $table->string('to_city')->nullable();
            $table->string('date', 16)->nullable();
            $table->string('dep', 12)->nullable();
            $table->string('arr', 12)->nullable();
            $table->string('cls', 16)->nullable();        // First · Business · Economy
            $table->string('dur', 16)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_legs');
        Schema::dropIfExists('flight_requests');
    }
};
