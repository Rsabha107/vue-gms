<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One invitation per guest per event — the envelope that carries the offered
        // matches, the rendered email, the RSVP deadline and the response state.
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_template_id')->nullable()
                  ->constrained('email_templates')->nullOnDelete();

            $table->enum('status', ['pending', 'accepted', 'declined', 'confirmed'])
                  ->default('pending');

            // the resolved/edited email captured at send time (templates can change later)
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->date('rsvp_deadline')->nullable();
            $table->string('sender')->nullable();
            $table->boolean('skip_email')->default(false);   // FR 4.2.14 — auto-accept

            // signed token for the public guest-facing RSVP link
            $table->string('rsvp_token', 64)->unique()->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
