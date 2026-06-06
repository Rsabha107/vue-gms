<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            if (!Schema::hasColumn('venues', 'name'))     $table->string('name')->nullable()->after('id');
            if (!Schema::hasColumn('venues', 'city'))     $table->string('city')->nullable()->after('name');
            if (!Schema::hasColumn('venues', 'country'))  $table->string('country', 2)->nullable()->after('city');
            if (!Schema::hasColumn('venues', 'capacity')) $table->integer('capacity')->nullable()->after('country');
            if (!Schema::hasColumn('venues', 'type'))     $table->string('type')->nullable()->after('capacity');
            if (!Schema::hasColumn('venues', 'notes'))    $table->text('notes')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['name', 'city', 'country', 'capacity', 'type', 'notes'],
                fn($col) => Schema::hasColumn('venues', $col)
            ));
        });
    }
};
