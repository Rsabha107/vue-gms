<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('vehicle_type');
            $table->string('vehicle_class')->nullable();
            $table->decimal('daily_rate', 10, 2);
            $table->string('currency', 3)->default('QAR');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('fleet_size');
            $table->unsignedInteger('assigned')->default(0);
            $table->date('cutoff_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_blocks');
    }
};
