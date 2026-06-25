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
        // Skip flight_requests and accommodation_requests - columns already added in earlier runs
        
        // Add tracking columns to transport_requests
        Schema::table('transport_requests', function (Blueprint $table) {
            $table->enum('initiated_by', ['guest', 'team'])->default('team');
            $table->enum('source', ['portal', 'manual', 'phone', 'email'])->default('manual');
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalation_reason')->nullable();
        });

        // Add tracking columns to arrival_departure_requests
        Schema::table('arrival_departure_requests', function (Blueprint $table) {
            $table->enum('initiated_by', ['guest', 'team'])->default('team');
            $table->enum('source', ['portal', 'manual', 'phone', 'email'])->default('manual');
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalation_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flight_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_officer_id']);
            $table->dropColumn(['initiated_by', 'source', 'assigned_officer_id', 'reminded_at', 'escalated_at', 'escalation_reason']);
        });

        Schema::table('accommodation_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_officer_id']);
            $table->dropColumn(['initiated_by', 'source', 'assigned_officer_id', 'reminded_at', 'escalated_at', 'escalation_reason']);
        });

        Schema::table('transport_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_officer_id']);
            $table->dropColumn(['initiated_by', 'source', 'assigned_officer_id', 'reminded_at', 'escalated_at', 'escalation_reason']);
        });

        Schema::table('arrival_departure_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_officer_id']);
            $table->dropColumn(['initiated_by', 'source', 'assigned_officer_id', 'reminded_at', 'escalated_at', 'escalation_reason']);
        });
    }
};
