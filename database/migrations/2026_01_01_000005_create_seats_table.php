<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Per-match seat instance. When a match picks a template, one row is generated
        // here for every seat in that template. Assignments / reservations / tickets /
        // hidden flags live PER MATCH and never bleed between matches that share a template.
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seating_template_id')->nullable()
                  ->constrained('seating_templates')->nullOnDelete();
            // human-readable, stable within a match: "{block}-{row2}-{col2}" e.g. "A-02-03"
            $table->string('code', 24);
            $table->string('block_code', 8);
            $table->string('row_label', 16);
            $table->unsignedSmallInteger('col');
            $table->enum('status', ['available', 'reserved', 'assigned', 'ticket'])
                  ->default('available');
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('res_label', 24)->nullable();      // org code / custom reservation label
            $table->boolean('hidden')->default(false);
            $table->timestamps();

            $table->unique(['game_match_id', 'code']);
            $table->index(['game_match_id', 'status']);
            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
