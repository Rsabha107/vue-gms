<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transport module (FR 4.6). Transport/vehicle requests for guests.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('transport_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();                 // TRN-001
            $table->string('status_id')->default('pending');  // FK to transport_statuses
            $table->foreign('status_id')->references('id')->on('transport_statuses')->onDelete('restrict');
            $table->string('type', 60);                       // VIP Transfer, VIP Escort, Motorcade, etc.
            $table->string('vehicle', 80)->nullable();        // Mercedes S-Class, BMW 7 Series, etc.
            $table->string('pickup_location', 120);           // HIA Terminal 1, Hotel name, etc.
            $table->string('dropoff_location', 120);          // Destination
            $table->string('datetime');                       // Pickup date and time
            $table->string('driver', 100)->default('TBD');    // Driver name
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_requests');
    }
};
