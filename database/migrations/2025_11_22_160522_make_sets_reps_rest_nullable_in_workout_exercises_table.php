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
        Schema::table('workout_exercises', function (Blueprint $table) {
            // Make sets, reps, and rest nullable for class-based workouts (Pilates, Core, Mobility)
            // where only video is needed without sets/reps
            $table->integer('sets')->nullable()->change();
            $table->string('reps', 50)->nullable()->change();
            $table->integer('rest')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            // Revert back to NOT NULL
            $table->integer('sets')->nullable(false)->change();
            $table->string('reps', 50)->nullable(false)->change();
            $table->integer('rest')->nullable(false)->change();
        });
    }
};
