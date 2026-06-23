<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_event', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_id');
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('not_invited'); // not_invited, invited, pending, accepted, declined, confirmed
            $table->timestamp('added_at')->useCurrent();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
            $table->unique(['guest_id', 'event_id']);
            $table->index('event_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_event');
    }
};
