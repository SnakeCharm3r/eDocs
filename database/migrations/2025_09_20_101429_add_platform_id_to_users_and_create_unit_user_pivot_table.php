<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add platform_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('platform_id')
                ->nullable()
                ->constrained('platforms')
                ->nullOnDelete();
        });

        Schema::create('unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('unit_id')
                ->constrained('units')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        // Drop unit_user pivot table
        Schema::dropIfExists('unit_user');

        // Remove platform_id from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['platform_id']);
            $table->dropColumn('platform_id');
        });
    }
};
