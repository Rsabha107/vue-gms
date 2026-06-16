<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Floor plans (banquet / gala TABLE seating — distinct from the stadium seat
 * grid). The builder is a self-contained canvas; the whole plan (tables,
 * features, chairs → guest ids) persists as one JSON document per event.
 * In the prototype this lived in localStorage; here it is server-of-truth.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('floorplans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Gala Dinner');
            $table->json('data')->nullable();   // { items:[...], scale, pan }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floorplans');
    }
};
