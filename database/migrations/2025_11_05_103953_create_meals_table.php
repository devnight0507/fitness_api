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
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_plan_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['Breakfast', 'Lunch', 'Dinner', 'Snack']);
            $table->string('time', 20);
            $table->string('name');
            $table->integer('calories');
            $table->json('ingredients');
            $table->text('instructions');
            $table->integer('order_index')->default(0);
            $table->timestamps();

            $table->index('nutrition_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
