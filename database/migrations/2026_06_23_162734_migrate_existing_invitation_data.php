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
        $notInvitedStatus = DB::table('invitation_statuses')->where('name', 'not_invited')->first();
        
        if ($notInvitedStatus) {
            DB::table('invitations')
                ->whereNull('status_id')
                ->update(['status_id' => $notInvitedStatus->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this data migration
    }
};
