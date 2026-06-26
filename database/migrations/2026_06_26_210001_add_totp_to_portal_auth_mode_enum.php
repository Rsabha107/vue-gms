<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE events MODIFY portal_auth_mode ENUM('signed_url','totp','magic_link','full_auth') DEFAULT 'signed_url'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE events MODIFY portal_auth_mode ENUM('signed_url','magic_link','full_auth') DEFAULT 'signed_url'");
    }
};
