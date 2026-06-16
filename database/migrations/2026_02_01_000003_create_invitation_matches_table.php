<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The matches an invitation OFFERS, plus the guest's per-match RSVP.
        // Row exists  ⇒ match offered.   response null ⇒ not yet answered.
        Schema::create('invitation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            $table->enum('response', ['yes', 'no'])->nullable();
            $table->timestamps();

            $table->unique(['invitation_id', 'game_match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_matches');
    }
};
