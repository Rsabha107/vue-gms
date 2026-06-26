<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('type')->default('invitation')->after('key');
            $table->string('cc')->nullable()->after('body');
            $table->string('bcc')->nullable()->after('cc');
            $table->boolean('enabled')->default(true)->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['type', 'cc', 'bcc', 'enabled']);
        });
    }
};
