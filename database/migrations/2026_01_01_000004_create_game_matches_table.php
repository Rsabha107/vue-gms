<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // "matches" is a reserved word in some engines; table is game_matches,
        // model is GameMatch. Each match belongs to an event + a venue and may pick
        // ONE seating template (nullable until set).
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seating_template_id')->nullable()
                  ->constrained('seating_templates')->nullOnDelete();
            $table->string('stage')->nullable();
            $table->string('label')->nullable();
            $table->string('team_a_name')->nullable();
            $table->string('team_a_flag', 8)->nullable();
            $table->string('team_b_name')->nullable();
            $table->string('team_b_flag', 8)->nullable();
            $table->date('date')->nullable();
            $table->string('day', 8)->nullable();
            $table->string('time', 8)->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('sold')->default(0);
            $table->boolean('featured')->default(false);
            $table->boolean('tbd')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
