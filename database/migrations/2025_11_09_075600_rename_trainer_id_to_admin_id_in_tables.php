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
        // Rename trainer_id to admin_id in users table
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('trainer_id', 'admin_id');
        });

        // Rename trainer_id to admin_id in workouts table
        Schema::table('workouts', function (Blueprint $table) {
            $table->renameColumn('trainer_id', 'admin_id');
        });

        // Rename trainer_id to admin_id in nutrition_plans table
        Schema::table('nutrition_plans', function (Blueprint $table) {
            $table->renameColumn('trainer_id', 'admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename admin_id back to trainer_id in users table
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('admin_id', 'trainer_id');
        });

        // Rename admin_id back to trainer_id in workouts table
        Schema::table('workouts', function (Blueprint $table) {
            $table->renameColumn('admin_id', 'trainer_id');
        });

        // Rename admin_id back to trainer_id in nutrition_plans table
        Schema::table('nutrition_plans', function (Blueprint $table) {
            $table->renameColumn('admin_id', 'trainer_id');
        });
    }
};
