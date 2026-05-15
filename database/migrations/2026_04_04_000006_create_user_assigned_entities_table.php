<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_assigned_entities')) {
            Schema::create('user_assigned_entities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('division_id');
                $table->timestamps();

                $table->unique(['user_id', 'division_id']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('division_id')->references('id')->on('divisions')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_assigned_entities')) {
            Schema::dropIfExists('user_assigned_entities');
        }
    }
};
