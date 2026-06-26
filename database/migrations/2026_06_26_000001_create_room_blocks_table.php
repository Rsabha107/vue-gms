<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('hotel_code')->nullable();
            $table->string('hotel_name');
            $table->string('room_type');
            $table->decimal('rate', 10, 2);
            $table->string('currency', 3)->default('QAR');
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('allotment');
            $table->unsignedInteger('picked_up')->default(0);
            $table->date('cutoff_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_blocks');
    }
};
