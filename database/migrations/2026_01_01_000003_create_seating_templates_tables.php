<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // A reusable seating blueprint that belongs to a VENUE. A venue can have many.
        Schema::create('seating_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // Blocks within a template (A / B / C ...). `code` is the short id used in seat codes.
        Schema::create('seating_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seating_template_id')->constrained()->cascadeOnDelete();
            $table->string('code', 8);                        // 'A', 'B', 'C'
            $table->string('label');                          // 'Block A'
            $table->enum('tier', ['VVIP', 'VIP'])->default('VIP');
            $table->unsignedInteger('position')->default(0);  // order within the template
            $table->timestamps();
            $table->unique(['seating_template_id', 'code']);
        });

        // Rows within a block. Each row carries its own seat count, aisle positions and walkway.
        Schema::create('seating_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seating_block_id')->constrained()->cascadeOnDelete();
            $table->string('label', 16);                      // '1', '2', '4B' ...
            $table->unsignedSmallInteger('seats');            // seat count in this row
            $table->json('aisles')->nullable();               // [2,7] = aisle after seat 2 and 7
            $table->boolean('walkway')->default(false);       // horizontal gap after this row
            $table->unsignedInteger('position')->default(0);  // order within the block
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seating_rows');
        Schema::dropIfExists('seating_blocks');
        Schema::dropIfExists('seating_templates');
    }
};
