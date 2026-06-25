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
        Schema::table('invitations', function (Blueprint $table) {
            // Service mode: how this guest's services will be managed
            $table->enum('service_mode', ['portal', 'white_glove', 'hybrid'])
                ->default('white_glove')
                ->after('status_id');
            
            // Portal access tracking
            $table->timestamp('portal_sent_at')->nullable()->after('service_mode');
            $table->timestamp('portal_accessed_at')->nullable()->after('portal_sent_at');
            $table->timestamp('portal_last_accessed_at')->nullable()->after('portal_accessed_at');
            $table->string('portal_token')->nullable()->unique()->after('portal_last_accessed_at');
            $table->timestamp('portal_token_expires_at')->nullable()->after('portal_token');
            
            // Service completion tracking
            $table->boolean('services_completed')->default(false)->after('portal_token_expires_at');
            $table->timestamp('services_completed_at')->nullable()->after('services_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn([
                'service_mode',
                'portal_sent_at',
                'portal_accessed_at',
                'portal_last_accessed_at',
                'portal_token',
                'portal_token_expires_at',
                'services_completed',
                'services_completed_at',
            ]);
        });
    }
};
