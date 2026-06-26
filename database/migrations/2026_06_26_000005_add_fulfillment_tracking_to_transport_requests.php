<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transport_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('fulfilled_by_id')->nullable()->after('notes');
            $table->unsignedBigInteger('fulfills_request_id')->nullable()->after('fulfilled_by_id');

            $table->foreign('fulfilled_by_id')->references('id')->on('transport_requests')->nullOnDelete();
            $table->foreign('fulfills_request_id')->references('id')->on('transport_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transport_requests', function (Blueprint $table) {
            $table->dropForeign(['fulfilled_by_id']);
            $table->dropForeign(['fulfills_request_id']);
            $table->dropColumn(['fulfilled_by_id', 'fulfills_request_id']);
        });
    }
};
