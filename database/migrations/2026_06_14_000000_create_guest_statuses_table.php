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
        Schema::create('guest_statuses', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('name');
            $table->string('label');
            $table->string('color', 7)->nullable();
            $table->string('bg_color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default statuses
        DB::table('guest_statuses')->insert([
            [
                'id' => 'invited',
                'name' => 'invited',
                'label' => 'Invited',
                'color' => '#1d4ed8',
                'bg_color' => '#dbeafe',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'pending',
                'name' => 'pending',
                'label' => 'Pending',
                'color' => '#a16207',
                'bg_color' => '#fef9c3',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'accepted',
                'name' => 'accepted',
                'label' => 'Accepted',
                'color' => '#15803d',
                'bg_color' => '#dcfce7',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'confirmed',
                'name' => 'confirmed',
                'label' => 'Confirmed',
                'color' => '#15803d',
                'bg_color' => '#dcfce7',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'declined',
                'name' => 'declined',
                'label' => 'Declined',
                'color' => '#dc2626',
                'bg_color' => '#fee2e2',
                'sort_order' => 5,
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
        Schema::dropIfExists('guest_statuses');
    }
};
