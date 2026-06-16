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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('guest_id', 10);
            $table->unsignedBigInteger('event_id')->nullable();
            $table->enum('status', ['draft', 'sent', 'confirmed', 'declined'])->default('draft');
            $table->string('subject', 255)->nullable();
            $table->text('body')->nullable();
            $table->string('rsvp_token', 64)->unique()->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('guest_id')
                ->references('id')
                ->on('guests')
                ->onDelete('cascade');

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
