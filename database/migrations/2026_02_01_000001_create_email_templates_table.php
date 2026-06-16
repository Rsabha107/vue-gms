<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Reusable, editable email templates with merge tags ({{guest_name}} etc).
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();      // slug, e.g. 'default-invite'
            $table->string('name');               // 'Default invite'
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
