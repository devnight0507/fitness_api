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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'student'])->default('student')->after('password');
            $table->string('avatar_path')->nullable()->after('role');
            $table->decimal('weight', 5, 2)->nullable()->after('avatar_path');
            $table->decimal('height', 5, 2)->nullable()->after('weight');
            $table->integer('age')->nullable()->after('height');
            $table->text('goal')->nullable()->after('age');
            $table->foreignId('admin_id')->nullable()->after('goal')->constrained('users')->onDelete('set null');

            $table->index('role');
            $table->index('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['admin_id']);
            $table->dropColumn(['role', 'avatar_path', 'weight', 'height', 'age', 'goal', 'admin_id']);
        });
    }
};
