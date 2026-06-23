<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('invitation_statuses')
            ->where('name', 'draft')
            ->update([
                'name' => 'not_invited',
                'label' => 'Not Invited',
                'description' => 'Guest has not been invited yet',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('invitation_statuses')
            ->where('name', 'not_invited')
            ->update([
                'name' => 'draft',
                'label' => 'Draft',
                'description' => 'Invitation is being prepared',
            ]);
    }
};
