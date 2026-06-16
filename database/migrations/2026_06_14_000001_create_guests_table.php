<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('name');
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('title')->nullable();
            $table->string('guestType')->default('local'); // local or international
            $table->string('qid', 20)->nullable(); // Qatar ID
            $table->string('tier');
            $table->string('group_id')->nullable();
            $table->string('nationality', 2)->nullable();
            $table->string('status_id', 20)->default('invited');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('host')->nullable();
            $table->string('hotel')->nullable();
            $table->text('dietaryNotes')->nullable();
            $table->text('notes')->nullable();
            $table->json('facilities')->nullable(); // For tracking flight, accommodation, etc.
            $table->json('companionList')->nullable(); // Array of { name, relation }
            $table->integer('companions')->default(0); // Count of companions
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('status_id')->references('id')->on('guest_statuses')->onDelete('restrict');

            $table->index('status_id');
            $table->index('tier');
            $table->index('group_id');
            $table->index('nationality');
            $table->index('guestType');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
