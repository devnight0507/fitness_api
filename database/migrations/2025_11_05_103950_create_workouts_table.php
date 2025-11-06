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
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('category', ['Gym', 'Home', 'Outdoor', 'HIIT', 'Cardio', 'Strength']);
            $table->string('duration', 50);
            $table->enum('level', ['Beginner', 'Intermediate', 'Advanced']);
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('video_path')->nullable();
            $table->integer('video_duration')->nullable()->comment('Duration in seconds');
            $table->foreignId('trainer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('level');
            $table->index('trainer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
