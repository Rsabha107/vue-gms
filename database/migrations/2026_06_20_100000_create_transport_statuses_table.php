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
        Schema::create('transport_statuses', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('name');
            $table->string('label');
            $table->string('color', 7)->nullable();
            $table->string('bg_color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default transport statuses
        DB::table('transport_statuses')->insert([
            [
                'id' => 'pending',
                'name' => 'pending',
                'label' => 'Pending',
                'color' => '#a16207',
                'bg_color' => '#fef9c3',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'confirmed',
                'name' => 'confirmed',
                'label' => 'Confirmed',
                'color' => '#3f7d52',
                'bg_color' => '#e6f0e7',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'cancelled',
                'name' => 'cancelled',
                'label' => 'Cancelled',
                'color' => '#6b7280',
                'bg_color' => '#f3f4f6',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_statuses');
    }
};
